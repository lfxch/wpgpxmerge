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
        echo ( strlen($wpq->get('id')) > 0  || $wpq->get('action') == 'save' ) ? 'Edit Map' : 'Create new Map'; ?></h2>
    <br /><a class="button" href="?page=wpgpxmerge&act=overview">Back to Overview</a>
    <br />
    <?php
    /** ===========================================================
     *          SAVE & LOAD
     */
        $map = new wpgpxmerge_map();
        $lib = null;
        if($wpq->get('action') == 'save'){
            print '<div class="log_div">merging tracks...<pre id="log" style="display: none;">';
            $map->apply($_POST);
            $lib = $map->save();
            print '</pre> done.<br /><br />';
            if($lib !== null) {
                print 'Total Distance: '.$lib->getTotalDistance().NL;
                print 'Points: ' . $lib->points_raw . NL;
                print 'Points Reduced: ' . $lib->points_reduced . NL;
                print 'Points Exported: ' . $lib->points_exported . NL;
                print 'Reduced: ' . round($lib->points_reduced / ($lib->points_raw / 100), 1) . '%' . NL;
                print 'map saved as ' . $lib->outfile . ' ( ' . round(filesize($lib->outfile) / 1024 / 1024, 1)
                    . ' MBytes)<br /><br />';
            }
            print '(<a id="toggleLog" href="#show"><i>Show Log</i></a>)</div><br /><br >';
        }else{
            if(array_key_exists('id',$_GET)){
                $map->id = $_GET['id'];
            }
            $map->load();
        }
    /**
     * ===========================================================*/
    ?>

    <form action="" method="post">
        <?php wp_nonce_field('update-options') ?>

        <!-- ===========================================================
                GENERIC OPTIONS
        ============================================================ -->
        <table class="form-table">
            <?php if( strlen($map->id) > 1): ?>
                <tr>
                    <th scope="row" style="vertical-align: middle">Embedding Code:</th>
                    <td>
                        <pre id="wgmm_embedd"><?php echo
                            htmlentities('[gpxmerge id="'.$map->id.'"]',ENT_QUOTES,'utf-8');  ?></pre>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <th scope="row">Name:</th>
                <td>
                    <input type="text" style="width:200px;" value="<?php echo htmlentities($map->name); ?>"
                           id="wpgpxmerge_name" name="wpgpxmerge_name">
                </td>
            </tr>
        </table>

        <!-- ===========================================================
                    TABS
        ============================================================ -->
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab nav-tab-active" href="#settings">Settings</a>
            <a class="nav-tab" href="#tracks">Tracks</a>
        </h2>

        <!-- ===========================================================
                    PROPERTIES
        ============================================================ -->
        <section class="admin_tab" id="settings">
        <table class="form-table">
            <tr>
                <th scope="row">Smart Reducing:</th>
                <td>
                    <input type="checkbox" value="true" id="wpgpxmerge_reduce" name="wpgpxmerge_reduce"
                        <?php echo $map->reduce === true ? 'checked="checked"' : '' ?> >
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
                           id="wpgpxmerge_reduce_ftol" name="wpgpxmerge_reduce_ftol">
                    <i>If Smart Reducing is enable, it can be fine tuned with
                        this Option. If a Track Point between two Points differs
                        less than &lt;float&gt; meters, ommit it. </i>
                </td>
            </tr>
            <tr>
                <th scope="row">Linear Reducing:</th>
                <td>
                    <input type="checkbox" value="true" id="wpgpxmerge_reduce_lin" name="wpgpxmerge_reduce_lin"
                        <?php echo $map->reduce_lin === true ? 'checked="checked"' : '' ?> >
                    <i>Enable Linear Reducing; Linear Reducing ommits
                        waypoints which are Closer then specified Value
                        to each other. Linear Reducing leads to some inaccuracy.</i>
                </td>
            </tr>
            <tr>
                <th scope="row">Linear Reducing Tolerance:</th>
                <td>
                    <input type="text" value="<?php echo $map->reduce_ftol_lin; ?>" style="width:50px;"
                           id="wpgpxmerge_reduce_ftol_lin" name="wpgpxmerge_reduce_ftol_lin">
                    <i>If Linear Reducing is enabled, skip waypoints that are closer then &lt;int&gt; meters to each other</i>
                </td>
            </tr>

            <tr>
                <th scope="row">Map Width:</th>
                <td>
                    <input type="text" value="<?php echo $map->map_width; ?>" style="width:50px;"
                           id="wpgpxmerge_map_width" name="wpgpxmerge_map_width">
                    <i></i>
                </td>
            </tr>
            <tr>
                <th scope="row">Map Height:</th>
                <td>
                    <input type="text" value="<?php echo $map->map_height; ?>" style="width:50px;"
                           id="wpgpxmerge_map_height" name="wpgpxmerge_map_height">
                    <i></i>
                </td>
            </tr>
        </table>
        </section>

        <!-- ===========================================================
                    TRACKS
        ============================================================ -->
        <section class="admin_tab" id="tracks" style="display: none;">
            <?php function wpgpxmerge_track_selection_template($filename,$relative_path){
                ?>
                <div class="bbox trackfile">
                    <div class="trackfile_column avail"><button class="add">&#x25c0;</button></div>
                    <div class="trackfile_column fullwidth">
                        <span class="relpath">/uploads<?php echo $relative_path; ?></span>
                        <span class="filename"><?php echo $filename; ?></span>
                        <input type="checkbox" name="wpgpxmerge_tracks[]"
                               value="<?php echo $relative_path.$filename; ?>">
                    </div>
                    <div class="trackfile_column b_up sel"><button class="up">&#x25B2;</button></div>
                    <div class="trackfile_column b_down sel"><button class="down">&#x25BC;</button></div>
                    <div class="trackfile_column sel"><button class="del">&#x2715;</button></div>
                </div>
                <?php
            }
            ?>
            <br /><br />
            <div class="bbox track_selection_table">
                <div class="bbox track_selection_column">
                    <strong>Selected Tracks</strong><br /><br />
                    <div id="selected_tracks" class="bbox wpgpxmerge_track_list">
                        <?php
                        foreach ($map->tracks as $gpx) {
                            $filename = basename($gpx);
                            $relative_path = substr($gpx, 0, strlen($filename)*-1 );
                            wpgpxmerge_track_selection_template($filename,$relative_path);
                        }
                        ?>
                    </div>
                </div>
                <div class="bbox track_selection_column">
                    <strong>Available Tracks</strong><br /><br />
                    <div id="available_tracks" class="bbox wpgpxmerge_track_list">
                        <?php
                        $files = $map->findGPXfiles();
                        foreach ($map->getFoundGPXFiles() as $gpx) {
                            $filename = basename($gpx);
                            $relative_path = substr($gpx, strlen($map->_upload_base), strlen($filename)*-1 );
                            if(in_array($relative_path.$filename,$map->tracks))
                                continue;
                            wpgpxmerge_track_selection_template($filename,$relative_path);
                        }
                        ?>
                    </div>
                </div>
            </div><br /><br />
        </section>

        <h2 class="nav-tab-wrapper"></h2>

        <!-- ===========================================================
                SAVE
        ============================================================ -->
        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td>
                    <input type="hidden" name="action" value="save" />
                    <input type="hidden" name="wpgpxmerge_id" value="<?php echo $map->id; ?>" />
                    <input class="button-primary" type="submit" value="Save Map" />
                </td>
            </tr>
        </table>
    </form>

<!-- /WRAP -->
</div>

<!-- ===========================================================
            JS SCRIPTS
============================================================ -->
<script>
    jQuery('#selected_tracks input[type=checkbox]').prop('checked',true);

    jQuery('.wpgpxmerge_track_list .add').on('click', function(){
        jQuery('#selected_tracks').append( jQuery(this).parents('div.trackfile') );
        jQuery(this).parents('div.trackfile').find('input[type=checkbox]').prop('checked',true);
        return false;
    });

    jQuery('.wpgpxmerge_track_list .del').on('click', function(){
        jQuery('#available_tracks').append( jQuery(this).parents('div.trackfile') );
        jQuery(this).parents('div.trackfile').find('input[type=checkbox]').prop('checked',false);
        return false;
    });

    jQuery('.wpgpxmerge_track_list .up').on('click', function(){
        jQuery(this).parents('div.trackfile').prev().before( jQuery(this).parents('div.trackfile') );
        return false;
    });
    jQuery('.wpgpxmerge_track_list .down').on('click', function(){
        jQuery(this).parents('div.trackfile').next().after( jQuery(this).parents('div.trackfile') );
        return false;
    });

    //jQuery(window).on('resize',function(){
    //    jQuery('.wpgpxmerge_track_list').css('max-width', (jQuery(window).width() -30) + 'px' );
    //});

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

    jQuery('h2.nav-tab-wrapper a').on('click',function(){
        jQuery('h2.nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery('section.admin_tab').hide();
        jQuery(this).addClass('nav-tab-active');
        jQuery(jQuery(this).attr('href')).show();
        return false;
    });

</script>

