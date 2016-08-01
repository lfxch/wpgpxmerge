<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 28.06.16
 * Time: 12:35
 */


class wpgpxmerge_lib {

    /**
     * @var array
     */
    private $files = array();

    /**
     * @var wpgpxmerge_trk[]
     */
    public $tracks = array();

    /**
     * disance in meters from all gpx tracks
     *
     * @var int
     */
    public $distance = 0;
    public $points_raw = 0;
    public $points_reduced = 0;
    public $points_exported = 0;
    public $reduce_points = true;
    public $reduce_ftol = 3;
    public $reduce_lin = true;
    public $reduce_ftol_lin = 1;
    public $last_outfile = '';
    public $partial_merge = true; // use this for very large merge operations, it uses less memory
    public $parts = array();
    public $outfile;
    public $ignore_segments = true;
    public $ignore_tracks = true;

    public $orig_size = 0;

    /**
     * wpgpxmerge_lib constructor.
     */
    function __construct(){
    }

    public function getTotalDistance(){
        return $this->distance;
    }

    /**
     * add a gpx file
     *
     * @param $fp
     * @return bool
     */
    function addFile($fp){
        if(file_exists($fp)){
            $this->files[] = $fp;
            return $this->parseGPXfile($fp);
        }
        return false;
    }


    /**
     * parse file int tracks, segments, trackpoints
     *
     * @param $file
     * @return bool
     */
    function parseGPXfile($file){
        $x = new XMLReader();

        if(!$x->open($file)){
            return false;
        }

        $stat = new wpgpxmerge_trackstats();
        $stat->orig_file = $file;

        $this->orig_size += filesize($file);

        $track = new wpgpxmerge_trk();
        $segment = new wpgpxmerge_trkseg();
        $point = new wpgpxmerge_trkpt();

        $tracks = array();

        $lastpoint = null;
        $lastpoint_dist = null;
        $buffer = null;

        while($x->read()){
            if($x->nodeType == XMLReader::ELEMENT){
                switch ($x->name){
                    case 'trk':
                        $track = new wpgpxmerge_trk();
                        $tracks[] = $track;
                        break;
                    case 'trkseg':
                        $segment = new wpgpxmerge_trkseg();
                        $track->addSegment($segment);
                        break;
                    case 'trkpt':
                        $point = new wpgpxmerge_trkpt();
                        $point->importDOMNode( $x->expand() );

                        $this->points_raw++;

                        if($lastpoint_dist !== null){
                            $dist = wpgpxmerge_trkpt::distance($point,$lastpoint_dist);
                            $this->distance+=$dist;
                            $stat->distance += $dist;

                            $ele = $point->ele - $lastpoint_dist->ele;

                            if($ele > 0){
                                $stat->up += $ele;
                            }else{
                                $stat->down += $ele;
                            }

                            $stat->ele += $ele;

                            $stat->start = min($stat->start,$point->getTimeStamp());
                            $stat->stop = max($stat->stop,$point->getTimeStamp());
                        }else{
                            $stat->start = $point->getTimeStamp();
                            $stat->stop = $point->getTimeStamp();
                            $stat->start_lat = $point->lat;
                            $stat->start_lon = $point->lon;

                        }

                        $lastpoint_dist = $point;

                        if($lastpoint !== null){

                            $dist = wpgpxmerge_trkpt::distance($point,$lastpoint);

                            if($this->reduce_lin){
                                if($dist < $this->reduce_ftol_lin){
                                    $buffer = $point;
                                    $this->points_reduced++;
                                    $x->next();
                                    break;
                                }
                            }


                        }

                        $segment->addPoint($point);
                        $buffer = null;
                        $lastpoint = $point;
                        $x->next();
                        break;
                }
            }
        }

        $stat->stop_lat = $lastpoint_dist->lat;
        $stat->stop_lon = $lastpoint_dist->lon;

        // very last point
        if($buffer !== null){
            $segment->addPoint($buffer);
        }


        if(count($tracks) > 0){
            if($this->reduce_points == true){
                while($this->reduce_v2($tracks) > 0){
                    // reducing until there is nothing to reduce!
                }
            }

            if($this->partial_merge){
                $this->tracks = $tracks;
                $file = $this->outfile.'_part'.(count($this->parts)+1);
                $this->merge($this->ignore_tracks,$this->ignore_segments,$file);
                $this->parts[] = $file;

            }else{
                $this->tracks = array_merge($this->tracks,$tracks);
            }


        }

        return $stat;
    }

    /**
     * merge loaded gpx file into one
     * preserve tracks an track segments with $ignoreTracks = false an $ignoreSegments = false
     * if outfile = null -> return xml, else write to this file
     *
     * @param bool $ignoreTracks
     * @param bool $ignoreSegments
     * @return mixed
     */
    public function merge($ignoreTracks = true, $ignoreSegments = true,$outfile = null){

        $verylastpoint = null;


        $header = '<?xml version=\'1.0\' encoding=\'UTF-8\' standalone=\'yes\' ?>
<gpx version="1.1" creator="merged bei wpgpxmerge_lib" xmlns="http://www.topografix.com/GPX/1/1" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
    xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">';
        $footer = '</gpx>';


        if($this->partial_merge && $outfile !== null) {
            $xml = <<<XML
<?xml version='1.0' encoding='UTF-8' standalone='yes' ?>
<part>
</part>
XML;

        }elseif($this->partial_merge){


            file_put_contents($this->outfile,$header);

            foreach($this->parts as $file){
                $raw = file_get_contents($file);
                $start = strpos($raw,'<part>');
                $stop = strpos($raw,'</part>');
                $content = substr($raw, $start+6, $stop - ($start+6) );
                file_put_contents($this->outfile,$content,FILE_APPEND);
                unlink($file);
            }

            file_put_contents($this->outfile,$footer,FILE_APPEND);

            return true;
        }else{
            $xml = $header.$footer;

        }

        $sxe = new SimpleXMLElement($xml);

        $p_track = null;
        $p_segment = null;

        if($ignoreTracks === true){
            $p_track = $sxe->addChild('trk');
        }
        if($ignoreSegments === true){
            $p_segment = $p_track->addChild('trkseg');
        }

        // TRACK
        foreach ($this->tracks as $track){

            if($ignoreTracks === false || $p_track === null){
                $p_track = $sxe->addChild('trk');
                $p_segment = null;
            }

            // SEGMENT
            foreach ($track->getSegments() as $segment){

                if($ignoreSegments === false || $p_segment === null){
                    $p_segment = $p_track->addChild('trkseg');
                }

                // POINT
                foreach($segment->getTrackPoints() as $point){

                    if($point->reduced == true)
                        continue;

                    $this->points_exported++;

                    $p_point = $p_segment->addChild('trkpt');
                    $point->addToSimpleXMLElement($p_point);
                }

            }
        }

        if($outfile !== '') {
            file_put_contents($outfile, $sxe->asXML());

        }else{
            if ($this->outfile === null) {
                return $sxe->asXML();
            } else {
                file_put_contents($this->outfile, $sxe->asXML());
            }

        }


    }

    /**
     * weg punkte reduzieren welche keinen einfluss auf das gesamt bild haben. wenn also 3 punkte auf
     * der selben linie liegen kann der mittlere weggelassen werden. mit $ftol kann bestimmt werden
     * wie kulant die funktion sein soll. $ftol bezieht sich auf anzahl Meter welche der mittlere Punkt
     * maximal abweichen darf. Standardmässig 3 meter, damit kann sogar noch bestimmt werden auf welcher
     * strassenseite sich der wegpunkt befindet. Je höher der Wert, desto mehr wegpunkte werden weggelassen,
     * desto ungenauer und kürzer wird der track.
     *
     * für grosse übersichen kann man problemlos auf bis zu 20m gehen und kann immer noch erkennen auf
     * welcher strasse sich der wegpunkt befindet
     *
     *
     * return count of reduced points, so this function could applied multiple times until it returns 0.
     *
     * @param $tracks
     * @return int
     */
    public function reduce_v2(&$tracks){

        $reduced = 0;
        $points = 0;

        /**
         * @var wpgpxmerge_trkpt $buf1
         */
        $buf1 = null;

        /**
         * @var wpgpxmerge_trkpt $buf2
         */
        $buf2 = null;

        /**
         * @var wpgpxmerge_trkpt $buf3
         */
        $buf3 = null;
        $last_point_of_segment = null;

        foreach ($tracks as $track){
            foreach ($track->getSegments() as $segment){
                foreach($segment->getTrackPoints() as $point){
                    $last_point_of_segment = $point;
                    if($point->reduced == true){
                        continue;
                    }
                    $points++;

                    if($buf1===null){
                        $buf1 = $point;
                        continue;
                    }
                    if($buf2===null){
                        $buf2 = $point;
                        continue;
                    }
                    $buf3 = $point;

                    $a = wpgpxmerge_trkpt::distance($buf2,$buf3); // distanz p2 - p3
                    $b = wpgpxmerge_trkpt::distance($buf1,$buf2); // distanz p1 - p2
                    $c = wpgpxmerge_trkpt::distance($buf1,$buf3); // distanz p1 - p3

                    $alpha = 0;
                    $hc = 0;
                    if( 2*$b*$c > 0){
                        $alpha = acos( ($b*$b + $c*$c - $a*$a) / (2*$b*$c) );
                        $hc = $b * sin($alpha);
                    }


                    if($hc < $this->reduce_ftol){
                        $this->points_reduced++;
                        $reduced++;
                        $buf2->reduced = true;
                        //$buf2->cleanup();
                        $buf1 = $buf3;
                        $buf2 = null;

                    }else{
                        $buf1 = $buf2;
                        $buf2 = $buf3;
                    }
                }
                if($last_point_of_segment->reduced == true){
                    $last_point_of_segment->reduced = false;
                    $reduced--;
                }

            }
        }



        return $reduced;
    }

}







