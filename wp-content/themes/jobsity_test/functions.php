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

	wp_enqueue_script(
		'jobsity-wishlist',
		get_template_directory_uri() . '/assets/scripts/wishlist.js',
		array(),
		'1.0.0',
		true
	);

	wp_localize_script(
		'jobsity-wishlist',
		'JobsityWishlist',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'jobsity_wishlist' ),
			'loginUrl'    => wp_login_url(),
			'isLoggedIn'  => is_user_logged_in(),
			'strings'     => array(
				'add'           => __( 'Add to wishlist', '' ),
				'remove'        => __( 'Remove from wishlist', '' ),
				'loginRequired' => __( 'Please log in to use the wishlist.', '' ),
				'tryAgain'      => __( 'Something went wrong. Please try again.', '' ),
			),
		)
	);
}

add_action( 'wp', 'jobsity_maybe_increment_node_views' );

/**
 * Increment views counter for movies and actors.
 *
 * Keeps it intentionally simple: no admin/previews/AJAX.
 */
function jobsity_maybe_increment_node_views() {
	if ( is_admin() || wp_doing_ajax() || is_preview() ) {
		return;
	}

	if ( ! is_singular( array( 'movie', 'actor' ) ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( $post_id <= 0 ) {
		return;
	}

	$views = (int) get_post_meta( $post_id, 'node_views', true );
	$views = max( 0, $views ) + 1;

	update_post_meta( $post_id, 'node_views', $views );
}

/**
 * Compute the search score using (V * P) / D.
 *
 * V = node_views meta (defaults to 0)
 * P = popularity meta (defaults to 1)
 * D = days since release (movies) or days since publish (actors); never < 1
 *
 * @param WP_Post $post Post object.
 * @return float
 */
function jobsity_search_score( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return 0.0;
	}

	$post_id = (int) $post->ID;

	$views = (int) get_post_meta( $post_id, 'node_views', true );
	$views = max( 0, $views );

	$popularity = get_post_meta( $post_id, 'popularity', true );
	$popularity = is_numeric( $popularity ) ? (float) $popularity : 1.0;
	$popularity = max( 0.0, $popularity );

	$days = 1;

	if ( 'movie' === $post->post_type ) {
		$rd = get_post_meta( $post_id, 'release_date', true );
		if ( is_string( $rd ) && '' !== $rd ) {
			$ts = strtotime( $rd . ' UTC' );
			if ( false !== $ts ) {
				$days = (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
			}
		}
	} else {
		$ts = get_post_time( 'U', true, $post );
		if ( $ts ) {
			$days = (int) floor( ( time() - (int) $ts ) / DAY_IN_SECONDS );
		}
	}

	$days = max( 1, $days );

	return ( $views * $popularity ) / $days;
}

/**
 * Produce a readable snippet for search results.
 *
 * @param WP_Post $post Post object.
 * @return string
 */
function jobsity_search_snippet( $post ) {
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$post_id = (int) $post->ID;

	$excerpt = get_the_excerpt( $post );
	if ( is_string( $excerpt ) && '' !== trim( $excerpt ) ) {
		return wp_strip_all_tags( $excerpt );
	}

	$content = '';

	if ( 'movie' === $post->post_type ) {
		$overview = get_post_meta( $post_id, 'overview', true );
		if ( is_string( $overview ) && '' !== trim( $overview ) ) {
			$content = $overview;
		}
	} elseif ( 'actor' === $post->post_type ) {
		$bio = get_post_meta( $post_id, 'bio', true );
		if ( is_string( $bio ) && '' !== trim( $bio ) ) {
			$content = $bio;
		}
	}

	if ( '' === $content ) {
		$content = $post->post_content;
	}

	$content = wp_strip_all_tags( (string) $content );

	return wp_trim_words( $content, 24, '…' );
}

/**
 * Get the current user's wishlist (movie IDs).
 *
 * @param int $user_id User ID, defaults to current.
 * @return int[]
 */
function jobsity_get_user_movie_wishlist( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( $user_id <= 0 ) {
		return array();
	}

	$list = get_user_meta( $user_id, 'movie_wishlist', true );
	if ( ! is_array( $list ) ) {
		return array();
	}

	$list = array_map( 'absint', $list );
	$list = array_values( array_filter( $list ) );

	return array_values( array_unique( $list ) );
}

/**
 * Check if a movie is in the user's wishlist.
 *
 * @param int $movie_id Movie post ID.
 * @param int $user_id  User ID, defaults to current.
 * @return bool
 */
function jobsity_is_movie_in_wishlist( $movie_id, $user_id = 0 ) {
	$movie_id = absint( $movie_id );
	if ( $movie_id <= 0 ) {
		return false;
	}

	return in_array( $movie_id, jobsity_get_user_movie_wishlist( $user_id ), true );
}

add_action( 'wp_ajax_jobsity_toggle_wishlist', 'jobsity_ajax_toggle_wishlist' );
add_action( 'wp_ajax_nopriv_jobsity_toggle_wishlist', 'jobsity_ajax_toggle_wishlist' );

/**
 * AJAX: Toggle movie in wishlist.
 */
function jobsity_ajax_toggle_wishlist() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'jobsity_wishlist' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', '' ) ), 403 );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Login required.', '' ) ), 401 );
	}

	$movie_id = isset( $_POST['movie_id'] ) ? absint( $_POST['movie_id'] ) : 0;
	if ( $movie_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid movie.', '' ) ), 400 );
	}

	$p = get_post( $movie_id );
	if ( ! $p || 'movie' !== $p->post_type || 'publish' !== $p->post_status ) {
		wp_send_json_error( array( 'message' => __( 'Invalid movie.', '' ) ), 400 );
	}

	$user_id = get_current_user_id();
	$list    = jobsity_get_user_movie_wishlist( $user_id );

	$exists = in_array( $movie_id, $list, true );

	if ( $exists ) {
		$list = array_values( array_diff( $list, array( $movie_id ) ) );
		update_user_meta( $user_id, 'movie_wishlist', $list );
		wp_send_json_success(
			array(
				'in_wishlist' => false,
				'movie_id'    => $movie_id,
			)
		);
	}

	$list[] = $movie_id;
	$list   = array_values( array_unique( array_map( 'absint', $list ) ) );

	update_user_meta( $user_id, 'movie_wishlist', $list );

	wp_send_json_success(
		array(
			'in_wishlist' => true,
			'movie_id'    => $movie_id,
		)
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
