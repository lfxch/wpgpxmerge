<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 01.08.16
 * Time: 10:33
 */
class wpgpxmerge_trkseg {

    /**
     * @var wpgpxmerge_trkpt[]
     */
    private $points = array();

    /**
     * @param wpgpxmerge_trkpt $trkpt
     * @return bool
     */
    public function addPoint(wpgpxmerge_trkpt $trkpt){
        $this->points[] = $trkpt;
        return true;
    }

    /**
     * @return wpgpxmerge_trkpt[]
     */
    public function getTrackPoints(){
        return $this->points;
    }

    /**
     * @return string
     */
    public function __toString(){
        return 'points: '.count($this->points);
    }
}