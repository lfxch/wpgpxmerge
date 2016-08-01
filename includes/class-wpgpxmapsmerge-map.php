<?php

/**
 * helper for data store
 *
 * @since      1.0.0
 * @package    Wpgpxmapsmerge
 * @subpackage Wpgpxmapsmerge/includes
 * @author     Christian Moser <chris@lfx.ch>
 */
class Wpgpxmapsmergemap {

	public $reduce = true;
	public $reduce_ftol = 1;
    public $reduce_lin = true;
    public $reduce_ftol_lin = 1;
    public $map_width = '100%';
    public $map_height = '450px';
	public $name = '';
	public $id = 0;
	public $maps = array();
	public $datastore = 'wpgpxmapsmergedata';
    public $_messages = array();
    public $_do_not_generate = false;
    public $stats_per_gpx = array();

    const ERROR = 'error';
    const WARNING = 'warn';
    const INFO = 'info';
    const DEBUG = 'debug';

    private function dbg($message, $type = self::INFO){
        $this->_messages[] = array($message,$type);
    }


    /**
     * @return Wpgpxmapsmergemap[]
     */
    public function getAllMaps(){

        $maps = array();

        $wp_upload_dir = wp_upload_dir()['basedir'];
        $maps_ds = $wp_upload_dir.'/'.$this->datastore;

        if(!is_dir($maps_ds))
            return $maps;

        $folders = scandir($maps_ds);

        foreach($folders as $folder){

            if(file_exists($maps_ds.'/'.$folder.'/data.json')){
                $map = new Wpgpxmapsmergemap();
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

//var_dump($this);

        if($this->id === 0){
            $this->id = uniqid('map');
        }

        $wp_upload_dir = wp_upload_dir()['basedir'];
        $meta_file = $wp_upload_dir.'/'.$this->datastore.'/'.$this->id.'/data.json';

        @mkdir($wp_upload_dir.'/'.$this->datastore);
        @mkdir($wp_upload_dir.'/'.$this->datastore.'/'.$this->id);

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
        if(is_array($data['maps']) && count($data['maps'])>0){

            // merge it
            $lib = new libgpxmerge();
            $lib->reduce_ftol = $data['reduce_ftol'];
            $lib->reduce_points = $data['reduce'];
            $lib->reduce_ftol_lin = $data['reduce_ftol_lin'];
            $lib->reduce_lin = $data['reduce_lin'];
            $lib->outfile = $wp_upload_dir.'/'.$this->datastore.'/'.$this->id.'/map.gpx';
            $lib->partial_merge = true;

            $y = 1;

            $total_dist = 0;

            foreach($data['maps'] as $map){
                print 'parsing file '.$y.' of '.count($data['maps']).' ('.$map.')...'; $y++;
                $stats = $lib->addFile($wp_upload_dir.'/'.$map);
                print ''. round($lib->getTotalDistance()/1000) .'km. (Original Filesize: '.
                    round(filesize($wp_upload_dir.'/'.$map)/1024) .' Kb, reduced to '.
                    round(filesize( $lib->parts[count($lib->parts)-1] )/1024).' Kb)<br />';
                $total_dist = $lib->getTotalDistance();
                if($stats instanceof wpgpxmergetrackstats)
                    print '...'.$stats->__toString().'<br />';

                $this->stats_per_gpx[] = $stats->asArr();

            }
            print 'Processed '.count($data['maps']).' files, total '.round($lib->orig_size / 1024 / 1024, 1).' Mb<br />';
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

    public function findGPXfiles($path = null){

        if($this->_upload_base === null){
            $this->_upload_base = wp_upload_dir()['basedir'];
        }
        if($path === null){
            $path = $this->_upload_base;
        }

        if(!preg_match('/\/$/',$path))
            $path .= '/';

        $contents = @scandir($path);
        //var_dump($path);print '<br />';

        if(!is_array($contents))
            return false;
//var_dump();
        foreach($contents as $fsi){

            if($fsi == '..' || $fsi == '.' )
                continue;



            if(is_dir($path.$fsi)){

                if($fsi !== 'wpgpxmapsmergedata')
                    $this->findGPXfiles($path.$fsi);
            }elseif (is_file($path.$fsi)){
                //print $fsi;
                if(preg_match('/\.gpx$/i',$fsi)){
                    $this->_found_files[filectime($path.$fsi).'_'.uniqid()] = $path.$fsi;
                    //print '!!!!';
                }
                //print '<br />';
            }else{

                // something else or unreadable
            }

        }

        sort($this->_found_files);
        ksort($this->_found_files);

        return true;
    }
    public function getFoundGPXFiles(){
        return $this->_found_files;
    }

    /**
     * apply post data to this object
     *
     * @param $data
     */
    public function apply($data){

        if(array_key_exists('wpgpxmapsmerge_reduce',$data) && $data['wpgpxmapsmerge_reduce'] == 'true'){
            $this->reduce = true;
        }else{
            $this->reduce = false;
        }

        if(array_key_exists('wpgpxmapsmerge_reduce_lin',$data) && $data['wpgpxmapsmerge_reduce_lin'] == 'true'){
            $this->reduce_lin = true;
        }else{
            $this->reduce_lin = false;
        }


        if(array_key_exists('wpgpxmapsmerge_name',$data)){
            $this->name = $data['wpgpxmapsmerge_name'];
        }

        if(array_key_exists('wpgpxmapsmerge_map_width',$data)){
            $this->name = $data['wpgpxmapsmerge_map_width'];
        }

        if(array_key_exists('wpgpxmapsmerge_map_height',$data)){
            $this->name = $data['wpgpxmapsmerge_map_height'];
        }

        if(array_key_exists('wpgpxmapsmerge_name',$data)){
            $this->name = $data['wpgpxmapsmerge_name'];
        }

        if(array_key_exists('wpgpxmapsmerge_reduceftol',$data)){
            $this->reduce_ftol = $data['wpgpxmapsmerge_reduceftol'];
        }

        if(array_key_exists('wpgpxmapsmerge_reduceftol_lin',$data)){
            $this->reduce_ftol_lin = $data['wpgpxmapsmerge_reduceftol_lin'];
        }


        if(array_key_exists('wpgpxmapsmerge_tracks',$data) && is_array($data['wpgpxmapsmerge_tracks']) ){
            $this->maps = $data['wpgpxmapsmerge_tracks'];
        }

        if(array_key_exists('wpgpxmapsmerge_donotgen',$data) && $data['wpgpxmapsmerge_donotgen'] == 'true'){
            $this->_do_not_generate = true;
        }else{
            $this->_do_not_generate = false;
        }

        if(array_key_exists('wpgpxmapsmerge_id',$data)){
            if(preg_match('/^[a-z0-9]+$/',$data['wpgpxmapsmerge_id'])){

                $wp_upload_dir = wp_upload_dir()['basedir'];

                if(is_dir( $wp_upload_dir.'/'.$this->datastore.'/'.$data['wpgpxmapsmerge_id'] )){
                    $this->id = $data['wpgpxmapsmerge_id'];
                }
            }
            
        }

    }

	/**
	 * load data from store
	 */
	public function load(){

		$wp_upload_dir = wp_upload_dir()['basedir'];

		if($this->id === 0
            || $this->id == '0'
            || preg_match('/\./',$this->id)
            || $this->id == ''
            || strlen($this->id) < 1
            ){

            //var_dump($this->id);
            return false;
        }

        //var_dump('hello');

		$meta_file = $wp_upload_dir.'/'.$this->datastore.'/'.$this->id.'/data.json';

		if(!file_exists($meta_file)){
			return false;
		}

		$settings_raw = file_get_contents($meta_file);
        
        $settings = unserialize($settings_raw);

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
