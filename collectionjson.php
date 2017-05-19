<?php
/*
Plugin Name: Linknest Collection JSON
Description: A plugin making every collection on Linknest avaliable as JSON. Making it easy to fetch from other apps.
Plugin URI: https://linknest.cc
Author: Urban Sandén
Version: 0.1.0
Author URI: https://urre.me
*/

// Load dotenv
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Add dates for rss feed
function collectionjson_rss_date( $timestamp = null ) {
	$timestamp = ($timestamp==null) ? time() : $timestamp;
	echo date(DATE_RSS, $timestamp);
}

// Add endpoint and specify EP mask
function collectionjson_add_endpoint() {
	add_rewrite_endpoint( 'json', EP_PERMALINK | EP_PAGES );
}

add_action( 'init', 'collectionjson_add_endpoint' );

// Check if /rssfeed is used
function collectionjson_template_redirect() {
	global $wp_query;
	if ( ! isset( $wp_query->query_vars['json'] )  )
		return;
	collectionjson_output_feed();
	exit;
}

add_action( 'template_redirect', 'collectionjson_template_redirect' );

// Output rss feed
function collectionjson_output_feed() {

	// Get collection object
	$post = get_queried_object();

	// Get links connected to this collection
	$posts = query_posts(array(
		'post_type' => 'link',
		'posts_per_page' => -1,
		'meta_key' => 'listid',
		'meta_value' => $post->ID
	) );

	foreach ($posts as $post) :

		$url = get_post_meta( $post->ID, 'url' );
		$screenshoturl = get_post_meta( $post->ID, 'screenshoturl' );

		$link_data[] = array(
		    'link'  => esc_url( get_permalink($post->ID) ),
		    'linkurl'  => esc_url( $url[0] ),
		    'image'  => esc_url( $screenshoturl[0] ),
		    'title' => html_entity_decode(get_the_title($post->ID)),
		);

	endforeach;
	wp_send_json( $link_data );

}

function collectionjson_activate() {
	collectionjson_add_endpoint();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'collectionjson_activate' );

function collectionjson_deactivate() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'collectionjson_deactivate' );
