<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 03.07.16
 * Time: 11:55
 */
$wpq = new WP_Query( $_REQUEST );
define('NL','<br />');
?>



<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); echo ' - ';
        echo strlen($wpq->get('id')) > 0 ? 'Edit Map' : 'Create new Map'; ?></h2>
    <a href="?page=wpgpxmapsmerge&act=overview">&lt;- Back to Overview</a>
    <br />

    <?php
        $map = new Wpgpxmapsmergemap();
        $lib = null;
        if($wpq->get('action') == 'save'){
            ?>
            <div class="log_div">Saving...
                <pre id="log" style="display: none;">
            <?php


            $map->apply($_POST);
            $lib = $map->save();
            if($lib !== null) {
                //print 'Dist: '.$lib->getTotalDistance().NL;
                print 'Points: ' . $lib->points_raw . NL;
                print 'Points Reduced: ' . $lib->points_reduced . NL;
                print 'Points Exported: ' . $lib->points_exported . NL;
                print 'Reduced: ' . round($lib->points_reduced / ($lib->points_raw / 100), 1) . '%' . NL;
                print 'map saved as ' . $lib->outfile . ' ( ' . round(filesize($lib->outfile) / 1024 / 1024, 1)
                    . ' MBytes)<br /><br />';
            }
            ?>
                    </pre>
            done. (<a id="toggleLog" href="#show"><i>Show Log</i></a>)</div>
                <script>
                    jQuery('#toggleLog').on('click',function(){
                        if(jQuery(this).attr('href') == '#show'){
                            jQuery('#log').show();
                            jQuery(this).attr('href','#hide');
                            jQuery(this).html('<i>Hide Log</i>');
                            return false;
                        }else{
                            jQuery('#log').hide();
                            jQuery(this).html('<i>Show Log</i>');
                            jQuery(this).attr('href','#show');
                            return false;
                        }
                    });
                </script>

            <?php
        }else{
            if(array_key_exists('id',$_GET)){
                $map->id = $_GET['id'];
            }
            $map->load();
        }
    ?>

<br /><br />


    <section class="tab_section" id="configuration">


        <form action="" method="post">
            <?php wp_nonce_field('update-options') ?>

            <table class="form-table">
                <?php if( strlen($wpq->get('id')) > 0): ?>
                    <tr>
                        <th scope="row" style="vertical-align: middle">Embedding Code:</th>
                        <td>
                            <span id="wgmm_embedd"><?php echo htmlentities('[gpxm id="'.$map->id.'"]',ENT_QUOTES,'utf-8');  ?></span>
                            <!--<pre id="wgmm_embedd"><?php //echo '[sgpx gpx="/wp-content/uploads/'
                            //.$map->datastore.'/'.$map->id.'/map.gpx"]';  ?></pre>-->
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Name:</th>
                    <td>
                        <input type="text" style="width:200px;" value="<?php echo htmlentities($map->name); ?>"
                               id="wpgpxmapsmerge_name" name="wpgpxmapsmerge_name">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Smart Reducing:</th>
                    <td>
                        <input type="checkbox" value="true" id="wpgpxmapsmerge_reduce" name="wpgpxmapsmerge_reduce"
                            <?php echo $map->reduce === true || $map->id === 0 ? 'checked="checked"' : '' ?> >
                        <i>Enable Smart Reducing, Try to reduce some Track Points which are not necessary (e.g.
                            multiple Points on a straight Line). Smart Reducing
                            ist much more accurate then Linear reducing, but not nearly
                            as efficient with very large tracks.</i>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Smart Reducing Tolerance:</th>
                    <td>
                        <input type="text" value="<?php echo $map->reduce_ftol; ?>" style="width:50px;"
                               id="wpgpxmapsmerge_reduceftol" name="wpgpxmapsmerge_reduceftol">
                        <i>If Smart Reducing is enable, it can be fine tuned with
                            this Option. If a Track Point between two Points differs
                            less than &lt;float&gt; meters, ommit it. </i>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Linear Reducing:</th>
                    <td>
                        <input type="checkbox" value="true" id="wpgpxmapsmerge_reduce_lin" name="wpgpxmapsmerge_reduce_lin"
                            <?php echo $map->reduce_lin === true || $map->id === 0 ? 'checked="checked"' : '' ?> >
                        <i>Enable Linear Reducing; Linear Reducing ommits
                            waypoints which are Closer then specified Value
                            to each other. Linear Reducing leads to some inaccuracy.</i>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Linear Reducing Tolerance:</th>
                    <td>
                        <input type="text" value="<?php echo $map->reduce_ftol_lin; ?>" style="width:50px;"
                               id="wpgpxmapsmerge_reduceftol_lin" name="wpgpxmapsmerge_reduceftol_lin">
                        <i>If Linear Reducing is enabled, skip waypoints that are closer then &lt;int&gt; meters to each other</i>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Map Width:</th>
                    <td>
                        <input type="text" value="<?php echo $map->map_width; ?>" style="width:50px;"
                               id="wpgpxmapsmerge_map_width" name="wpgpxmapsmerge_map_width">
                        <i></i>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Map Height:</th>
                    <td>
                        <input type="text" value="<?php echo $map->map_height; ?>" style="width:50px;"
                               id="wpgpxmapsmerge_map_height" name="wpgpxmapsmerge_map_height">
                        <i></i>
                    </td>
                </tr>

                <tr>
                    <th scope="row">GPX Tracks:</th>
                    <td>
                        <strong>Available Tracks</strong><br /><br />

                        <div id="available_tracks" class="wgmm_tracks">
                            <?php

                            $files = $map->findGPXfiles();
                            if(is_array($map->getFoundGPXFiles())) {
                                foreach ($map->getFoundGPXFiles() as $gpx) {



                                    $filename = basename($gpx);
                                    $relpath = substr($gpx, strlen($map->_upload_base), strlen($filename)*-1 );

                                    if(in_array($relpath.$filename,$map->maps)){
                                        continue;
                                    }
                                    ?>
                                    <div class="trackfile">
                                        <div class="cell">
                                            <span class="relpath">/uploads<?php echo $relpath; ?></span>
                                            <span class="filename"><?php echo $filename; ?></span>
                                            <input type="checkbox" name="wpgpxmapsmerge_tracks[]"
                                                   value="<?php echo $relpath.$filename; ?>">
                                        </div>
                                        <div class="cell avail"><button class="add">&#x271a;</button></div>
                                        <div class="cell b_up sel"><button class="up">&#x25B2;</button></div>
                                        <div class="cell b_down sel"><button class="down">&#x25BC;</button></div>
                                        <div class="cell sel"><button class="del">&#x2715;</button></div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div><br /><br />

                        <strong>Selected Tracks</strong><br /><br />

                        <div id="selected_tracks" class="wgmm_tracks">
                            <?php
                            if(is_array($map->maps)) {
                                foreach ($map->maps as $gpx) {
                                    $filename = basename($gpx);
                                    $relpath = substr($gpx, 0, strlen($filename)*-1 );
                                    ?>
                                    <div class="trackfile">
                                        <div class="cell">
                                            <span class="relpath">/uploads<?php echo $relpath; ?></span>
                                            <span class="filename"><?php echo $filename; ?></span>
                                            <input type="checkbox" name="wpgpxmapsmerge_tracks[]"
                                                   value="<?php echo $relpath.$filename; ?>">
                                        </div>
                                        <div class="cell avail"><button class="add">&#x271a;</button></div>
                                        <div class="cell b_up sel"><button class="up">&#x25B2;</button></div>
                                        <div class="cell b_down sel"><button class="down">&#x25BC;</button></div>
                                        <div class="cell sel"><button class="del">&#x2715;</button></div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>

                        <script>

                            jQuery('#selected_tracks input[type=checkbox]').prop('checked',true);

                            jQuery('.wgmm_tracks .add').on('click', function(){
                                jQuery('#selected_tracks').append( jQuery(this).parents('div.trackfile') );
                                jQuery(this).parents('div.trackfile').find('input[type=checkbox]').prop('checked',true);
                                return false;
                            });

                            jQuery('.wgmm_tracks .del').on('click', function(){
                                jQuery('#available_tracks').append( jQuery(this).parents('div.trackfile') );
                                jQuery(this).parents('div.trackfile').find('input[type=checkbox]').prop('checked',false);
                                return false;
                            });

                            jQuery('.wgmm_tracks .up').on('click', function(){
                                jQuery(this).parents('div.trackfile').prev().before( jQuery(this).parents('div.trackfile') );
                                return false;
                            });
                            jQuery('.wgmm_tracks .down').on('click', function(){
                                jQuery(this).parents('div.trackfile').next().after( jQuery(this).parents('div.trackfile') );
                                return false;
                            });

                            jQuery(window).on('resize',function(){
                                jQuery('.wgmm_tracks').css('max-width', (jQuery(window).width() -30) + 'px' );
                            });

                        </script>


                    </td>
                </tr>
                <tr>
                    <th scope="row">Don't generate the Map now:</th>
                    <td>
                        <input type="checkbox" value="true" id="wpgpxmapsmerge_dont" name="wpgpxmapsmerge_dont">
                        <i>Do not generate the map now, just save the definition</i>
                    </td>
                </tr>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <input type="hidden" name="action" value="save" />
                        <input type="hidden" name="wpgpxmapsmerge_id" value="<?php echo $map->id; ?>" />
                        <input class="button-primary" type="submit" value="Save Map" />
                    </td>
                </tr>

            </table>

        </form>
        
        
        
    </section>

    <?php if( strlen($wpq->get('id')) > 0): ?>
    <?php $file = wp_upload_dir()['basedir'].'/'.$map->datastore.'/'.$map->id.'/map.gpx'; ?>
    <?php if(file_exists($file)) : ?>
    <br /><br />
    <section class="tab_section" id="preview" >


        <h2>Preview</h2><br /><br />

        <div id="map" style="width: <?php echo $map->map_width; ?>; height: <?php echo $map->map_height; ?>"></div>

        <?php
        $prev = new libgpxmerge();
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
                    $west = min($east,$trackpoint->lon);


                    $points[] = '{lat: '.$trackpoint->lat.', lng: '.$trackpoint->lon.'}';
                }
            }
        }
        ?>
        <script>
            function initMap() {
                var map = new google.maps.Map(document.getElementById('map'), {
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
                <th>Distanz</th>
                <th>Auf</th>
                <th>Ab</th>
                <th>Ele</th>
                <th>Start</th>
                <th>Stop</th>
            </tr>
            <?php foreach($map->stats_per_gpx as $stat_raw) : ?>
                <tr><?php $stat = new wpgpxmergetrackstats($stat_raw); ?>
                    <td><?php echo date('d.m.Y',$stat->start); ?></td>
                    <td><?php echo round( $stat->distance/ 1000); ?>km</td>
                    <td><?php echo round($stat->up); ?>m</td>
                    <td><?php echo round($stat->down); ?>m</td>
                    <td><?php echo round($stat->ele); ?>m</td>
                    <td><?php echo date('H:i',$stat->start); ?></td>
                    <td><?php echo date('H:i',$stat->stop); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>


    </section>

    <?php endif; ?>
    <?php endif; ?>

</div>

