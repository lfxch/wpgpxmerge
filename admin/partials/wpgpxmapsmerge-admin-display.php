<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://lfx.ch/
 * @since      1.0.0
 *
 * @package    Wpgpxmapsmerge
 * @subpackage Wpgpxmapsmerge/admin/partials
 */


$wpq = new WP_Query( $_REQUEST );

switch($wpq->get('act')){
	case 'new':
    case 'edit':
		include_once 'wpgpxmapsmerge-admin-edit.php';
		break;
	default:
		include_once 'wpgpxmapsmerge-admin-overview.php';
}


?>
