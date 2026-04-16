<?php
/**
 * Jobsity test theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'jobsity_theme_setup' );

function jobsity_theme_setup() {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
}

add_action( 'wp_enqueue_scripts', 'jobsity_enqueue_assets' );

function jobsity_enqueue_assets() {
	wp_enqueue_style(
		'jobsity-layouts',
		get_template_directory_uri() . '/assets/layouts/main.css',
		array(),
		'1.0.0'
	);
}

/**
 * Find a published movie post by TMDB id.
 *
 * @param int|string $tmdb_id TMDB movie id.
 * @return WP_Post|null
 */
function jobsity_find_movie_by_tmdb_id( $tmdb_id ) {
	if ( '' === $tmdb_id || null === $tmdb_id ) {
		return null;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'movie',
			'post_status'            => 'publish',
			'meta_key'               => 'tmdb_id',
			'meta_value'             => $tmdb_id,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return ! empty( $posts ) ? $posts[0] : null;
}

/**
 * YouTube watch URL to embed URL.
 *
 * @param string $url Trailer URL.
 * @return string
 */
function jobsity_youtube_embed_url( $url ) {
	if ( empty( $url ) ) {
		return '';
	}

	if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]+)#', $url, $m ) ) {
		return 'https://www.youtube.com/embed/' . $m[1];
	}

	return '';
}

/**
 * Poster image URL for a movie (featured image or TMDB poster_url meta).
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function jobsity_movie_poster_url( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$u = get_the_post_thumbnail_url( $post_id, 'large' );
		if ( $u ) {
			return $u;
		}
	}

	$u = get_post_meta( $post_id, 'poster_url', true );

	return is_string( $u ) ? $u : '';
}

/**
 * Photo URL for an actor (featured image or photo_url meta).
 *
 * @param int $post_id Actor post ID.
 * @return string
 */
function jobsity_actor_photo_url( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		$u = get_the_post_thumbnail_url( $post_id, 'large' );
		if ( $u ) {
			return $u;
		}
	}

	$u = get_post_meta( $post_id, 'photo_url', true );

	return is_string( $u ) ? $u : '';
}

/**
 * Gallery image URLs for an actor: featured/photo plus attachments, max $max.
 *
 * @param int $actor_id Actor post ID.
 * @param int $max    Max images.
 * @return string[]
 */
function jobsity_get_actor_gallery_urls( $actor_id, $max = 10 ) {
	$urls = array();

	$primary = jobsity_actor_photo_url( $actor_id );
	if ( $primary ) {
		$urls[] = $primary;
	}

	$attachments = get_posts(
		array(
			'post_type'              => 'attachment',
			'post_mime_type'         => 'image',
			'post_parent'            => $actor_id,
			'posts_per_page'         => $max,
			'orderby'                => 'menu_order ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
		)
	);

	foreach ( $attachments as $att ) {
		$u = wp_get_attachment_image_url( $att->ID, 'large' );
		if ( $u ) {
			$urls[] = $u;
		}
	}

	$urls = array_values( array_unique( $urls ) );

	return array_slice( $urls, 0, $max );
}

/**
 * Genre term names for a movie.
 *
 * @param int $movie_id Movie post ID.
 * @return string[]
 */
function jobsity_get_movie_genre_names( $movie_id ) {
	$terms = get_the_terms( $movie_id, 'genre' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$names = wp_list_pluck( $terms, 'name' );

	sort( $names );

	return $names;
}

/**
 * Format genre list for display.
 *
 * @param int $movie_id Movie post ID.
 * @return string
 */
function jobsity_movie_genres_string( $movie_id ) {
	$names = jobsity_get_movie_genre_names( $movie_id );

	return $names ? implode( ', ', $names ) : '';
}
