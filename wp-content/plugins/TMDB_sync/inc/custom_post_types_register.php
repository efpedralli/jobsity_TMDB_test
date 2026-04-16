<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ms_register_movie_cpt' );
add_action( 'init', 'ms_register_actor_cpt' );
add_action( 'init', 'ms_register_genre_taxonomy' );

function ms_register_movie_cpt() {
	$labels = array(
		'name'               => 'Movies',
		'singular_name'      => 'Movie',
		'menu_name'          => 'Movies',
		'name_admin_bar'     => 'Movie',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Movie',
		'new_item'           => 'New Movie',
		'edit_item'          => 'Edit Movie',
		'view_item'          => 'View Movie',
		'all_items'          => 'All Movies',
		'search_items'       => 'Search Movies',
		'not_found'          => 'No movies found.',
		'not_found_in_trash' => 'No movies found in Trash.',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'has_archive'        => true,
		'menu_icon'          => 'dashicons-video-alt2',
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'editor', 'thumbnail' ),
		'rewrite'            => array( 'slug' => 'movies' ),
	);

	register_post_type( 'movie', $args );
}


function ms_register_actor_cpt() {
	$labels = array(
		'name'               => 'Actors',
		'singular_name'      => 'Actor',
		'menu_name'          => 'Actors',
		'name_admin_bar'     => 'Actor',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Actor',
		'new_item'           => 'New Actor',
		'edit_item'          => 'Edit Actor',
		'view_item'          => 'View Actor',
		'all_items'          => 'All Actors',
		'search_items'       => 'Search Actors',
		'not_found'          => 'No actors found.',
		'not_found_in_trash' => 'No actors found in Trash.',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'has_archive'        => true,
		'menu_icon'          => 'dashicons-groups',
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'editor', 'thumbnail' ),
		'rewrite'            => array( 'slug' => 'actors' ),
	);

	register_post_type( 'actor', $args );
}


function ms_register_genre_taxonomy() {
	$labels = array(
		'name'              => 'Genres',
		'singular_name'     => 'Genre',
		'search_items'      => 'Search Genres',
		'all_items'         => 'All Genres',
		'edit_item'         => 'Edit Genre',
		'update_item'       => 'Update Genre',
		'add_new_item'      => 'Add New Genre',
		'new_item_name'     => 'New Genre Name',
		'menu_name'         => 'Genres',
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'hierarchical'      => false,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'genre' ),
	);

	register_taxonomy( 'genre', array( 'movie' ), $args );
}