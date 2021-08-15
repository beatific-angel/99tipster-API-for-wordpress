<?php 

/**
 * Trigger this file on plugin uninstall
 *
 * @package Super Side
 */

if( ! defined('WP_UNINSTALL_PLUGIN') ) { die; }

// Clear Database stored data
$sides = get_posts( array( 'post_type' => 'side', 'numberposts' => -1 ) );

// Run through each side and delete from db
foreach( $sides as $side ) {
	wp_delete_post($side->ID, true );
}

///// ALTERNATIVE METHOD //////

// Access the database via SQL
//global $wpdb;
//$wpdb->query( "DELETE FROM wp_posts WHERE post_type = 'side'" );
//$wpdb->query( "DELETE FROM wp_postmeta WHERE post_id NOT IN ( SELECT id FROM wp_posts)" );
//$wpdb->query( "DELETE FROM wp_term_relationships WHERE object_id NOT IN ( SELECT id FROM wp_posts)" );
