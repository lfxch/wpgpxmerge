<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 01.08.16
 * Time: 10:34
 */
class wpgpxmerge_trackstats {
    public $up = 0;
    public $down = 0;
    public $ele = 0;
    public $start;
    public $stop;
    public $distance = 0;
    public $start_lat;
    public $start_lon;
    public $stop_lat;
    public $stop_lon;
    public $orig_file;
    public $duration = 0;

    function __construct($data = array()){
        foreach($data as $k=>$v){
            if(property_exists($this,$k)){
                $this->$k = $v;
            }
        }
    }

    function __toString()
    {
        $s = 'Distance: '.$this->distance
            .', Up: '.$this->up
            .', Down: '.$this->down
            .', Ele: '.$this->ele
            .', Start: '.date('d.m.Y H:i:s', $this->start)
            .', Stop: '.date('d.m.Y H:i:s', $this->stop)
            .', Started @ lat: '.$this->start_lat.' / lng: '.$this->start_lon
            .', Stopped @ lat: '.$this->stop_lat.' / lng: '.$this->stop_lon;
        return $s;
    }

    function asArr(){
        $arr = array();
        foreach ($this as $k=>$v) {
            $arr[$k] = $v;
        }
        return $arr;
    }

    static function convertSeconds($seconds){

        $minutes = 0;
        $hours = 0;
        $days = 0;

        if($seconds >= 60){
            $minutes = floor($seconds/60);
            $seconds = $seconds % 60;
        }

        if($minutes >= 60){
            $hours = floor($minutes/60);
            $minutes = $minutes % 60;
        }

        if($hours >= 24){
            $days = floor($hours/24);
            $hours = $hours % 60;
        }

        $days = $days > 0 ? $days.'d ' : '';
        $rest = sprintf("%01dh %01dm %01ds",$hours,$minutes,$seconds);

        return $days.$rest;

    }
}