<?php
/**
 * Created by PhpStorm.
 * User: lightfly
 * Date: 03.07.16
 * Time: 11:55
 */

?>
<div class="wrap">

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

    <br />

<a class="button button-primary" href="?page=wpgpxmerge&act=edit">+ Add new Map</a>
    
    <?php $m = new wpgpxmerge_map(); $maps = $m->getAllMaps(); ?>

    <?php if(count($maps)>0): ?>

    <br /><br />
    
    <h3>Saved Maps</h3>

        <div class="gpxmerge_overview_maps">

            <?php foreach ($maps as $map) : ?>
                <a href="?page=wpgpxmerge&act=edit&id=<?php print $map->id; ?>">
                    <div>
                        <img width="32px" src="<?php echo plugin_dir_url( __FILE__ ).'../../img/map.png'; ?>" />
                    </div>
                    <div>
                        <span class="title"><?php print htmlentities($map->name,null,'iso-8859-1'); ?></span><br />
                        <span class="additional"><?php count($map->tracks); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>


    <?php endif; ?>

    </div>