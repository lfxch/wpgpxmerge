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

<a href="?page=wpgpxmapsmerge&act=edit">+ Add new Map</a>
    
    <?php $m = new Wpgpxmapsmergemap(); $maps = $m->getAllMaps(); ?>

    <?php if(count($maps)>0): ?>

    <br /><br />
    
    <h3>Saved Maps</h3>

        <div class="wqmm_maps_overview">

            <?php foreach ($maps as $map) : ?>
                <a href="?page=wpgpxmapsmerge&act=edit&id=<?php print $map->id; ?>">
                    <span>
                        <?php print htmlentities($map->name,null,'iso-8859-1'); ?>
                    </span><br />
                </a>
            <?php endforeach; ?>

        </div>


    <?php endif; ?>

    </div>