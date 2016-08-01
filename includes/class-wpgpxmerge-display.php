<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 01.08.16
 * Time: 10:49
 */
class wpgpxmerge_display {

    /**
     * Register Shortcodes
     */
    static function register_shortcodes(){
        
        $display = new self;
        
        add_shortcode( 'gpxmerge', array($display, 'map') );
    }

    /**
     * Generic Map Display
     *
     * @param $atts
     * @return bool
     */
    function map( $atts) {

        if(!is_array($atts))
            return 'invalid attributes';
        
        if(!array_key_exists('id',$atts))
            return 'missing id attribute';
        
        $map = new wpgpxmerge_map();
        $map->id = $atts['id'];
        if(!$map->load()){
            return 'no such map';
        }

        $file = wpgpxmerge_map::getDatastore().$map->id.'/map.gpx';

        $prev = new wpgpxmerge_lib();
        $prev->reduce_lin = false;
        $prev->reduce_points = false;
        $prev->partial_merge = false;
        $prev->addFile($file);

        $points = array();

        // bounds
        $north = null;
        $south = null;
        $east = null;
        $west = null;

        foreach($prev->tracks as $track){
            foreach($track->getSegments() as $segment){
                foreach($segment->getTrackPoints() as $trackpoint){

                    if($north === null){
                        $north = $trackpoint->lat;
                        $south = $trackpoint->lat;
                        $east = $trackpoint->lon;
                        $west = $trackpoint->lon;
                    }

                    $north = max($north,$trackpoint->lat);
                    $south = min($south,$trackpoint->lat);
                    $east = max($east,$trackpoint->lon);
                    $west = min($west,$trackpoint->lon);


                    $points[] = '{lat: '.$trackpoint->lat.', lng: '.$trackpoint->lon.'}';
                }
            }
        }

        $uniq = uniqid();

        ob_start();
        ?>

        <div id="map_<?php echo $uniq; ?>" style="width: <?php echo $map->map_width; ?>; height: <?php echo $map->map_height; ?>"></div>
        <script>
            function initMap() {
                var map = new google.maps.Map(document.getElementById('map_<?php echo $uniq; ?>'), {
                    zoom: 3,
                    center: {lat: <?php echo (($north+$south)/2); ?>, lng: <?php echo (($east+$west)/2); ?>},
                    mapTypeId: google.maps.MapTypeId.HYBRID
                });

                //var bounds = new google.maps.LatLngBounds();
                //map.setCenter(bounds.getCenter());
                //map.fitBounds(bounds);

                var Coordinates = [
                    <?php echo implode(",\n",$points); ?>
                ];

                var bounds = new google.maps.LatLngBounds();
                jQuery(Coordinates).each(function(){
                    var myLatLng = new google.maps.LatLng(this.lat, this.lng);
                    bounds.extend(myLatLng);
                });
                map.fitBounds(bounds);

                var Path = new google.maps.Polyline({
                    path: Coordinates,
                    geodesic: true,
                    strokeColor: '#FF0000',
                    strokeOpacity: 1.0,
                    strokeWeight: 2
                });

                Path.setMap(map);
            }
        </script>
        <?php //var_dump($north,$south,$west,$east); ?>
        <script async defer
                src="https://maps.googleapis.com/maps/api/js?callback=initMap"></script>

        <br /><br />

        <table class="wpgpxmerge_table" style="width: <?php echo $map->map_width; ?>">
            <tr>
                <th>Etappe</th>
                <th>&#x21FF;</th>
                <th>&#x2197;</th>
                <th>&#x2198;</th>
                <th>&#x21c5;</th>
                <th>&#x21a6;</th>
                <th>&#x21e5;</th>
            </tr>
            <?php foreach($map->stats_per_gpx as $stat_raw) : ?>
                <tr><?php $stat = new wpgpxmerge_trackstats($stat_raw); ?>
                    <td><?php echo date('d.m.Y',$stat->start); ?></td>
                    <td><?php echo round( $stat->distance/ 1000); ?>km</td>
                    <td><?php echo round($stat->up); ?>m</td>
                    <td><?php echo round($stat->down); ?>m</td>
                    <td><?php echo round($stat->ele); ?>m</td>
                    <td><?php echo date('H:i',$stat->start); ?></td>
                    <td><?php echo date('H:i',$stat->stop); ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total"><?php $total = $map->getStatTotal(); ?>
                <td>Total</td>
                <td><?php echo round( $total->distance/ 1000); ?>km</td>
                <td><?php echo round($total->up); ?>m</td>
                <td><?php echo round($total->down); ?>m</td>
                <td><?php echo round($total->ele); ?>m</td>
                <td colspan="2"><?php echo wpgpxmerge_trackstats::convertSeconds($total->duration); ?></td>
            </tr>
        </table>



        <?php
        return ob_get_clean();
        

    }
}