<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 01.08.16
 * Time: 10:33
 */
class wpgpxmerge_trk {

    /**
     * @var wpgpxmerge_trkseg[]
     */
    private $segments = array();

    /**
     * @param wpgpxmerge_trkseg $trkseg
     * @return bool
     */
    public function addSegment(wpgpxmerge_trkseg $trkseg){
        $this->segments[] = $trkseg;
        return true;
    }

    /**
     * @return wpgpxmerge_trkseg[]
     */
    public function getSegments(){
        return $this->segments;
    }

    /**
     * @return string
     */
    public function __toString(){
        return 'Segments: '.count($this->segments);
    }
}