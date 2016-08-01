<?php

/**
 * helper for data store
 *
 * @since      1.0.0
 * @package    wpgpxmerge
 * @subpackage wpgpxmerge/includes
 * @author     Christian Moser <chris@lfx.ch>
 */
class wpgpxmerge_map {

	public $reduce = true;
	public $reduce_ftol = 1;
    public $reduce_lin = true;
    public $reduce_ftol_lin = 1;
    public $map_width = '100%';
    public $map_height = '450px';
	public $name = '';
	public $id = 0;
	public $tracks = array();
	public $datastore = 'gpxmergedata';
    public $_messages = array();
    public $_do_not_generate = false;

    /**
     * @var wpgpxmerge_trackstats[]
     */
    public $stats_per_gpx = array();

    const ERROR = 'error';
    const WARNING = 'warn';
    const INFO = 'info';
    const DEBUG = 'debug';

    private function dbg($message, $type = self::INFO){
        $this->_messages[] = array($message,$type);
    }

    public static function getDatastore(){
        $wp_upload_dir = wp_upload_dir()['basedir'];
        return $wp_upload_dir.'/gpxmergedata/';
    }

    public static function getUploadDir(){
        $dir = wp_upload_dir()['basedir'];
        if(!preg_match('/\/$/',$dir))
            $dir .= '/';
        return $dir;
    }

    public function getStatTotal(){

        $total = new wpgpxmerge_trackstats();

        foreach($this->stats_per_gpx as $stat_raw){
            $stat = new wpgpxmerge_trackstats($stat_raw);
            $total->up += $stat->up;
            $total->down += $stat->down;
            $total->distance += $stat->distance;
            $total->duration += $stat->stop - $stat->start;
        }

        $total->ele = $total->up + $total->down;

        return $total;
    }


    /**
     * @return wpgpxmerge_map[]
     */
    public function getAllMaps(){

        $maps = array();

        $maps_ds = self::getDatastore();

        if(!is_dir($maps_ds))
            return $maps;

        $folders = scandir($maps_ds);

        foreach($folders as $folder){

            if(file_exists($maps_ds.'/'.$folder.'/data.json')){
                $map = new wpgpxmerge_map();
                $map->id = $folder;
                $map->load();
                $maps[] = $map;
            }

        }

        return $maps;
    }




	/**
	 * save map settings and generate the map
	 */
	public function save(){

        if($this->id === 0){
            $this->id = uniqid('map');
        }

        $meta_file = self::getDatastore().$this->id.'/data.json';

        @mkdir(self::getDatastore());
        @mkdir(self::getDatastore().$this->id);

        $data = array();
        foreach ($this as $k => $v){

            if(preg_match('/^_/',$k) || $k == 'id')
                continue;

            $data[$k] = $v;
        }


        if($this->name == '')
            $this->name = 'Created @ '.date('y-m-d H:i:s');

        $data_ser = serialize($data);
        file_put_contents($meta_file,$data_ser);

        $lib = null;

        // ---------------------------------------------------------------------------------------------
        if(is_array($this->tracks) && count($this->tracks)>0){

            // merge it
            $lib = new wpgpxmerge_lib();
            $lib->reduce_ftol = $this->reduce_ftol;
            $lib->reduce_points = $this->reduce;
            $lib->reduce_ftol_lin = $this->reduce_ftol_lin;
            $lib->reduce_lin = $this->reduce_lin;
            $lib->outfile = self::getDatastore().$this->id.'/map.gpx';
            $lib->partial_merge = true;

            $y = 1;

            $total_dist = 0;

            foreach($this->tracks as $map){
                print 'parsing file '.$y.' of '.count($this->tracks).' ('.$map.')...'; $y++;
                $stats = $lib->addFile(self::getUploadDir().$map);
                print ''. round($lib->getTotalDistance()/1000) .'km. (Original Filesize: '.
                    round(filesize(self::getUploadDir().$map)/1024) .' Kb, reduced to '.
                    round(filesize( $lib->parts[count($lib->parts)-1] )/1024).' Kb)<br />';
                $total_dist = $lib->getTotalDistance();
                if($stats instanceof wpgpxmerge_trackstats)
                    print '...'.$stats->__toString().'<br />';

                $this->stats_per_gpx[] = $stats->asArr();

            }
            print 'Processed '.count($this->tracks).' files, total '.round($lib->orig_size / 1024 / 1024, 1).' Mb<br />';
            print 'Total Distance (without Reducing): '.round($total_dist/1000).'km<br />';
            $lib->merge(true,true);
        }
        // ---------------------------------------------------------------------------------------------


        $data['stats_per_gpx'] = $this->stats_per_gpx;
        $data_ser = serialize($data);
        file_put_contents($meta_file,$data_ser);

        $this->dbg('map saved.');

        return $lib;
	}

    private $_found_files = array();
    public $_upload_base = null;

    /**
     * search upload dir for gpx files
     *
     * @param null $path
     * @return bool
     */
    public function findGPXfiles($path = null){

        if($this->_upload_base === null){
            $this->_upload_base = self::getUploadDir();
        }
        if($path === null){
            $path = $this->_upload_base;
        }

        if(!preg_match('/\/$/',$path))
            $path .= '/';

        $contents = @scandir($path);

        if(!is_array($contents))
            return false;

        foreach($contents as $fsi){

            if($fsi == '..' || $fsi == '.' )
                continue;

            if(is_dir($path.$fsi)){

                if($fsi !== 'gpxmergedata')
                    $this->findGPXfiles($path.$fsi);
            }elseif (is_file($path.$fsi)){
                if(preg_match('/\.gpx$/i',$fsi)){
                    $this->_found_files[filectime($path.$fsi).'_'.uniqid()] = $path.$fsi;
                }
            }else{
                // something else or unreadable
            }

        }

        sort($this->_found_files);
        ksort($this->_found_files);

        return true;
    }
    public function getFoundGPXFiles(){
        return is_array($this->_found_files) ? $this->_found_files : array();
    }

    /**
     * apply post data to this object
     *
     * @param $data
     * @return bool
     */
    public function apply($data){

        if(!is_array($data))
            return false;

        $this->reduce_lin = false;
        $this->reduce = false;

        foreach($data as $k => $v){

            $prop = str_replace('wpgpxmerge_','',$k);


            switch($prop){
                case 'reduce':
                case 'reduce_lin':
                    $this->$prop = $v == 'true' ? true : false;
                    break;
                case 'width':
                case 'height':
                case 'reduce_ftol':
                case 'reduce_ftol_lin':
                    if(is_numeric($v))
                        $this->$prop = $v;
                    break;
                case 'tracks':
                    if(is_array($v))
                        $this->$prop = $v;
                    break;
                case 'name':
                    $this->$prop = $v;
                    break;
                case 'id':
                    if(preg_match('/^[a-z0-9]+$/',$v)){
                        if(is_dir( self::getDatastore().$v )){
                            $this->id = $v;
                        }
                    }
                break;
                default:
                    // ignore
            }
        }

        return true;
    }

	/**
	 * load data from store
	 */
	public function load(){

		if($this->id === 0
            || $this->id == '0'
            || preg_match('/\./',$this->id)
            || $this->id == ''
            || strlen($this->id) < 1
            ){
            return false;
        }

		$meta_file = self::getDatastore().$this->id.'/data.json';
		if(!file_exists($meta_file)){
			return false;
		}

		$settings_raw = file_get_contents($meta_file);
        $settings = @unserialize($settings_raw);

        if(!is_array($settings))
            return false;
        
        foreach ($this as $k => $v){
            if(array_key_exists($k,$settings)){
                $this->$k = $settings[$k];
            }
        }
		
        return true;
	}

}
