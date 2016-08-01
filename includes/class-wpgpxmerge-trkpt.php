<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 01.08.16
 * Time: 10:33
 */
class wpgpxmerge_trkpt {

    /**
     * latitude
     * @var int
     */
    public $lat = 0;

    /**
     * longitude
     * @var int
     */
    public $lon = 0;

    /**
     * elevation
     * @var int
     */
    public $ele = 0;

    /**
     * raw time
     * @var string
     */
    public $time = '';

    /**
     * @var DOMNode
     */
    private $node;

    /**
     * reducer sets this to true if that point should be ommited
     *
     * @var bool
     */
    public $reduced = false;

    public function cleanup(){
        $this->lat = null;
        $this->lon = null;
        $this->ele = null;
        $this->time = null;
        $this->node = null;
    }

    /**
     * import dom node from xmlreader
     *
     * @param DOMNode $node
     * @return bool
     */
    public function importDOMNode( DOMNode $node ){

        $this->node = $node;

        $doc = new DOMDocument();
        $sxe = simplexml_import_dom($doc->importNode($node,true));

        //print $sxe->asXML();

        $this->ele = $sxe->ele;
        $this->time = $sxe->time;

        $attribs = (array) $sxe->attributes();
        $this->lat = $attribs["@attributes"]['lat'];
        $this->lon = $attribs["@attributes"]['lon'];

        return true;
    }

    /**
     * @return DOMNode
     */
    public function getNode(){
        return $this->node;
    }

    /**
     * get unix timestamp from raw time
     *
     * @return int
     */
    public function getTimeStamp(){
        return strtotime($this->time);
    }

    /**
     * debug output
     *
     * @return string
     */
    public function __toString(){
        return $this->getTimeStamp().' - long: '.$this->lon.' / lat: '.$this->lat.' @'.$this->ele.'m';
    }

    /**
     * return as xml string
     *
     * @return string
     */
    public function toXML(){
        $sxe = new SimpleXMLElement( '<trkpt />' );
        $this->addToSimpleXMLElement($sxe);
        $xml = $sxe->asXML();

        // oke, das ist doof, aber hab grad auf die schnelle nicht rausgefunden wie ich das sonst weg kriege
        $xml = preg_replace('/\<\?xml(.*)?>/Ui','',$xml);

        return $xml;
    }

    /**
     * add trackpoint to an existing simplexmlelement
     * @param $ele
     */
    public function addToSimpleXMLElement( $ele ){
        $ele->addAttribute('lat',$this->lat);
        $ele->addAttribute('lon',$this->lon);
        $ele->addChild('ele',$this->ele);
        $ele->addChild('time',$this->time);
    }

    /**
     * calculate distance (in meters) between two track points using Haversine
     * error rate +- 0.3%
     *
     * @return int
     */
    public static function distance(wpgpxmerge_trkpt $p1, wpgpxmerge_trkpt $p2){

        $R = 6371000; // avg earth radius
        $r_lat1 = deg2rad($p1->lat);
        $r_lat2 = deg2rad($p2->lat);
        $d_lat = deg2rad( $p2->lat - $p1->lat );
        $d_lon = deg2rad( $p2->lon - $p1->lon );

        $a = pow( sin($d_lat/2), 2) +
            cos($r_lat1) * cos($r_lat2) *
            pow( sin($d_lon/2), 2);

        $c = 2 * atan2( sqrt($a), sqrt(1-$a) );

        $dist = $R * $c;


        return $dist;
    }

}
