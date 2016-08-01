<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://lfx.ch/
 * @since             1.0.0
 * @package           Wpgpxmapsmerge
 *
 * @wordpress-plugin
 * Plugin Name:       WP-GPX-MAPS Merge Addon
 * Plugin URI:        http://lfx.ch/dev/wordpress/plugins/gpxmerge
 * Description:       Merge multiple GPX Files into one for display with the great WP-GPX-MAPS Addon.
 * Version:           0.1.0
 * Author:            Christian Moser
 * Author URI:        http://lfx.ch/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpgpxmapsmerge
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpgpxmapsmerge-activator.php
 */
function activate_wpgpxmapsmerge() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpgpxmapsmerge-activator.php';
	Wpgpxmapsmerge_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpgpxmapsmerge-deactivator.php
 */
function deactivate_wpgpxmapsmerge() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpgpxmapsmerge-deactivator.php';
	Wpgpxmapsmerge_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpgpxmapsmerge' );
register_deactivation_hook( __FILE__, 'deactivate_wpgpxmapsmerge' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpgpxmapsmerge.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpgpxmapsmerge() {

	$plugin = new Wpgpxmapsmerge();
	$plugin->run();

}
run_wpgpxmapsmerge();


//[foobar]
function wpgpxmerge_shortcode( $atts ){

	$id = $atts['id'];


	$map = new Wpgpxmapsmergemap();
	$map->id = $atts['id'];
	$map->load();


	$file = wp_upload_dir()['basedir'].'/'.$map->datastore.'/'.$map->id.'/map.gpx';

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



	<?php
	return ob_get_clean();
}
add_shortcode( 'gpxm', 'wpgpxmerge_shortcode' );