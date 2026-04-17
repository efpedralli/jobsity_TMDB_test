<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ms_sync_actors() {
	# Paged for faster testing
	for ( $page = 1; $page <= 1; $page++ ) {
		$data = ms_tmdb_get(
			'person/popular',
			array(
				'language' => 'en-US',
				'page'     => $page,
			)
		);

		if ( empty( $data['results'] ) || ! is_array( $data['results'] ) ) {
			continue;
		}

		foreach ( $data['results'] as $actor_summary ) {
			if ( empty( $actor_summary['id'] ) ) {
				continue;
			}

			ms_process_actor( $actor_summary['id'] );
		}
	}
}


function ms_process_actor( $tmdb_actor_id ) {
	$actor = ms_tmdb_get(
		'person/' . $tmdb_actor_id,
		array(
			'language' => 'en-US',
		)
	);

	if ( empty( $actor ) || empty( $actor['id'] ) ) {
		return;
	}

	$actor_post_id = ms_upsert_actor( $actor );

	if ( ! $actor_post_id ) {
		return;
	}

	ms_sync_actor_movies( $actor_post_id, $tmdb_actor_id );
	ms_sync_actor_gallery( $actor_post_id, $tmdb_actor_id );
}


function ms_upsert_actor( $actor ) {
	$tmdb_id = $actor['id'];

	$existing = get_posts(
		array(
			'post_type'   => 'actor',
			'meta_key'    => 'tmdb_id',
			'meta_value'  => $tmdb_id,
			'numberposts' => 1,
		)
	);

	if ( ! empty( $existing ) ) {
		$post_id = $existing[0]->ID;

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $actor['name'] ?? '',
				'post_content' => $actor['biography'] ?? '',
			)
		);
	} else {
		$post_id = wp_insert_post(
			array(
				'post_title'   => $actor['name'] ?? '',
				'post_content' => $actor['biography'] ?? '',
				'post_status'  => 'publish',
				'post_type'    => 'actor',
			)
		);
	}

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return false;
	}

	update_post_meta( $post_id, 'tmdb_id', $tmdb_id );
	update_post_meta( $post_id, 'popularity', $actor['popularity'] ?? '' );
	update_post_meta( $post_id, 'birthday', $actor['birthday'] ?? '' );
	update_post_meta( $post_id, 'place_of_birth', $actor['place_of_birth'] ?? '' );
	update_post_meta( $post_id, 'deathday', $actor['deathday'] ?? '' );
	update_post_meta( $post_id, 'website', $actor['homepage'] ?? '' );
	update_post_meta( $post_id, 'bio', $actor['biography'] ?? '' );

	if ( ! empty( $actor['profile_path'] ) ) {
		$photo_url = 'https://image.tmdb.org/t/p/w500' . $actor['profile_path'];

		update_post_meta( $post_id, 'photo_url', $photo_url );

		if ( ! has_post_thumbnail( $post_id ) ) {
			ms_attach_image_from_url( $photo_url, $post_id );
		}
	}

	return $post_id;
}


function ms_sync_actor_movies( $actor_post_id, $tmdb_actor_id ) {
	$data = ms_tmdb_get(
		'person/' . $tmdb_actor_id . '/movie_credits',
		array(
			'language' => 'en-US',
		)
	);

	if ( empty( $data['cast'] ) || ! is_array( $data['cast'] ) ) {
		return;
	}

	$related_movie_ids = array();

	// only 3 movies for faster testing
	$movies = array_slice( $data['cast'], 0, 3 );

foreach ( $movies as $movie_summary ) {

		if ( empty( $movie_summary['id'] ) ) {
			continue;
		}

		$movie_post_id = ms_upsert_movie_from_actor_credit( $movie_summary, $actor_post_id );

		if ( ! $movie_post_id ) {
			continue;
		}

		$related_movie_ids[] = $movie_post_id;
	}

	update_post_meta( $actor_post_id, 'related_movie_ids', array_values( array_unique( $related_movie_ids ) ) );
}


function ms_upsert_movie_from_actor_credit( $movie, $actor_post_id = 0 ) {
	$tmdb_id = $movie['id'];

	$existing = get_posts(
		array(
			'post_type'   => 'movie',
			'meta_key'    => 'tmdb_id',
			'meta_value'  => $tmdb_id,
			'numberposts' => 1,
		)
	);

	if ( ! empty( $existing ) ) {
		$post_id = $existing[0]->ID;

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $movie['title'] ?? '',
				'post_content' => $movie['overview'] ?? '',
			)
		);
	} else {
		$post_id = wp_insert_post(
			array(
				'post_title'   => $movie['title'] ?? '',
				'post_content' => $movie['overview'] ?? '',
				'post_status'  => 'publish',
				'post_type'    => 'movie',
			)
		);
	}

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return false;
	}

	update_post_meta( $post_id, 'tmdb_id', $tmdb_id );
	update_post_meta( $post_id, 'overview', $movie['overview'] ?? '' );
	update_post_meta( $post_id, 'release_date', $movie['release_date'] ?? '' );
	update_post_meta( $post_id, 'popularity', $movie['popularity'] ?? '' );

	if ( ! empty( $movie['poster_path'] ) ) {
		$poster_url = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];

		update_post_meta( $post_id, 'poster_url', $poster_url );

		if ( ! has_post_thumbnail( $post_id ) ) {
			ms_attach_image_from_url( $poster_url, $post_id );
		}
	}

	if ( $actor_post_id && ! empty( $movie['character'] ) ) {
		update_post_meta( $actor_post_id, 'character_name_' . $post_id, $movie['character'] );
	}

	if ( $actor_post_id ) {
		$related_actor_ids = get_post_meta( $post_id, 'related_actor_ids', true );

		if ( ! is_array( $related_actor_ids ) ) {
			$related_actor_ids = array();
		}

		if ( ! in_array( $actor_post_id, $related_actor_ids, true ) ) {
			$related_actor_ids[] = $actor_post_id;
			update_post_meta( $post_id, 'related_actor_ids', $related_actor_ids );
		}
	}

	ms_sync_movie_genres_from_summary( $post_id, $movie );

	return $post_id;
}


function ms_sync_movie_genres_from_summary( $post_id, $movie ) {
	if ( empty( $movie['genre_ids'] ) || ! is_array( $movie['genre_ids'] ) ) {
		return;
	}

	$genre_list = ms_tmdb_get(
		'genre/movie/list',
		array(
			'language' => 'en-US',
		)
	);

	if ( empty( $genre_list['genres'] ) || ! is_array( $genre_list['genres'] ) ) {
		return;
	}

	$term_ids = array();

	foreach ( $genre_list['genres'] as $genre_item ) {
		if ( empty( $genre_item['id'] ) || empty( $genre_item['name'] ) ) {
			continue;
		}

		if ( ! in_array( $genre_item['id'], $movie['genre_ids'], true ) ) {
			continue;
		}

		$term = term_exists( $genre_item['name'], 'genre' );

		if ( ! $term ) {
			$created = wp_insert_term(
				$genre_item['name'],
				'genre',
				array(
					'slug' => sanitize_title( $genre_item['name'] ),
				)
			);

			if ( is_wp_error( $created ) ) {
				continue;
			}

			$term_id = (int) $created['term_id'];
			update_term_meta( $term_id, 'tmdb_genre_id', $genre_item['id'] );
		} else {
			$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}

		$term_ids[] = $term_id;
	}

	$term_ids = array_filter( array_unique( array_map( 'intval', $term_ids ) ) );

	if ( ! empty( $term_ids ) ) {
		wp_set_object_terms( $post_id, $term_ids, 'genre' );
	}
}


function ms_upsert_actor_from_cast( $cast_member, $movie_post_id = 0 ) {
	$tmdb_id = $cast_member['id'] ?? 0;

	if ( ! $tmdb_id ) {
		return false;
	}

	$existing = get_posts(
		array(
			'post_type'   => 'actor',
			'meta_key'    => 'tmdb_id',
			'meta_value'  => $tmdb_id,
			'numberposts' => 1,
		)
	);

	if ( ! empty( $existing ) ) {
		$post_id = $existing[0]->ID;

		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $cast_member['name'] ?? '',
			)
		);
	} else {
		$post_id = wp_insert_post(
			array(
				'post_title'  => $cast_member['name'] ?? '',
				'post_status' => 'publish',
				'post_type'   => 'actor',
			)
		);
	}

	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return false;
	}

	update_post_meta( $post_id, 'tmdb_id', $tmdb_id );
	update_post_meta( $post_id, 'popularity', $cast_member['popularity'] ?? '' );

	if ( ! empty( $cast_member['profile_path'] ) ) {
		$photo_url = 'https://image.tmdb.org/t/p/w500' . $cast_member['profile_path'];

		update_post_meta( $post_id, 'photo_url', $photo_url );

		if ( ! has_post_thumbnail( $post_id ) ) {
			ms_attach_image_from_url( $photo_url, $post_id );
		}
	}

	if ( $movie_post_id ) {
		$related_movies = get_post_meta( $post_id, 'related_movie_ids', true );

		if ( ! is_array( $related_movies ) ) {
			$related_movies = array();
		}

		if ( ! in_array( $movie_post_id, $related_movies, true ) ) {
			$related_movies[] = $movie_post_id;
			update_post_meta( $post_id, 'related_movie_ids', $related_movies );
		}

		update_post_meta( $post_id, 'character_name_' . $movie_post_id, $cast_member['character'] ?? '' );
	}

	return $post_id;
}

function ms_sync_actor_gallery( $post_id, $tmdb_actor_id ) {
	$data = ms_tmdb_get(
		'person/' . $tmdb_actor_id . '/images',
		array()
	);

	if ( empty( $data['profiles'] ) || ! is_array( $data['profiles'] ) ) {
		update_post_meta( $post_id, 'gallery_urls', array() );
		return;
	}

	$urls = array();

	foreach ( $data['profiles'] as $profile ) {
		if ( empty( $profile['file_path'] ) ) {
			continue;
		}

		$urls[] = 'https://image.tmdb.org/t/p/w500' . $profile['file_path'];
		// only 10 images 
		if ( count( $urls ) >= 10 ) {
			break;
		}
	}

	$urls = array_values( array_unique( $urls ) );
	update_post_meta( $post_id, 'gallery_urls', $urls );
}