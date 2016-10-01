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

function collectionjson_urlbox( $url, $args) {

	// Get API Keys from .env
	$URLBOX_APIKEY = getenv('URLBOX_APIKEY');
	$URLBOX_SECRET = getenv('URLBOX_SECRET');

	$options['url'] = urlencode( $url );
	$options += $args;

	foreach ( $options as $key => $value ) {
		$_parts[] = "$key=$value";
	}

	$query_string = implode( "&", $_parts );
	$TOKEN = hash_hmac( "sha1", $query_string, $URLBOX_SECRET );

	return "https://api.urlbox.io/v1/$URLBOX_APIKEY/$TOKEN/png?$query_string";
}

// Check if /rssfeed is used
function collectionjson_template_redirect() {
	global $wp_query;
	if ( ! isset( $wp_query->query_vars['rssfeed'] )  )
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

	// URL Box options
	$options['width'] = "800";
	$options['height'] = "600";
	$options['full_page'] = 'false';
	$options['force'] = 'false';
	$options['thumb_width'] = '800';

	foreach ($posts as $post) :

		$url = get_post_meta( $post->ID, 'url' );
		$urlbox_image = collectionjson_urlbox($url[0], $options);

		$link_data[] = array(
		    'link'  => esc_url( get_permalink($post->ID) ),
		    'linkurl'  => esc_url( $url[0] ),
		    'image'  => esc_url( $urlbox_image ),
		    'title' => sanitize_title(get_the_title($post->ID)),
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
