<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

function ms_sync_movies() {
	# Paged for faster testing
	for ( $page = 1; $page <= 1; $page++ ) {
		$data = ms_tmdb_get(
			'movie/upcoming',
			array(
				'language' => 'en-US',
				'page'     => $page,
			)
		);

		if ( empty( $data['results'] ) ) {
			continue;
		}

		foreach ( $data['results'] as $movie_summary ) {
			if ( empty( $movie_summary['id'] ) ) {
				continue;
			}

			ms_process_movie( $movie_summary['id'] );
		}
	}
}

function ms_process_movie( $tmdb_movie_id ) {
	$movie = ms_tmdb_get(
		'movie/' . $tmdb_movie_id,
		array(
			'language'           => 'en-US',
			'append_to_response' => 'videos,reviews,similar,alternative_titles',
		)
	);

	if ( empty( $movie ) || empty( $movie['id'] ) ) {
		return;
	}

	$post_id = ms_upsert_movie( $movie );

	if ( ! $post_id ) {
		return;
	}

	ms_sync_movie_genres( $post_id, $movie );
	ms_sync_movie_cast( $post_id, $tmdb_movie_id );
}


function ms_upsert_movie( $movie ) {
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
				'post_title'   => $movie['title'],
				'post_content' => $movie['overview'] ?? '',
			)
		);
	} else {
		$post_id = wp_insert_post(
			array(
				'post_title'   => $movie['title'],
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
	update_post_meta( $post_id, 'original_language', $movie['original_language'] ?? '' );

	ms_save_movie_trailer( $post_id, $movie );
	ms_save_movie_alternative_titles( $post_id, $movie );
	ms_save_movie_production_companies( $post_id, $movie );
	ms_save_movie_reviews( $post_id, $movie );
	ms_save_movie_similar( $post_id, $movie );

	if ( ! empty( $movie['poster_path'] ) ) {
		$poster_url = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];

		update_post_meta( $post_id, 'poster_url', $poster_url );

		if ( ! has_post_thumbnail( $post_id ) ) {
			ms_attach_image_from_url( $poster_url, $post_id );
		}
	}

	return $post_id;
}

function ms_save_movie_trailer( $post_id, $movie ) {
	if ( empty( $movie['videos']['results'] ) || ! is_array( $movie['videos']['results'] ) ) {
		return;
	}

	$trailer_url = '';

	foreach ( $movie['videos']['results'] as $video ) {
		if (
			isset( $video['site'], $video['type'], $video['key'] ) &&
			'YouTube' === $video['site'] &&
			'Trailer' === $video['type']
		) {
			$trailer_url = 'https://www.youtube.com/watch?v=' . $video['key'];
			break;
		}
	}

	if ( ! empty( $trailer_url ) ) {
		update_post_meta( $post_id, 'trailer_url', $trailer_url );
	}
}

function ms_save_movie_alternative_titles( $post_id, $movie ) {
	if ( empty( $movie['alternative_titles']['titles'] ) || ! is_array( $movie['alternative_titles']['titles'] ) ) {
		return;
	}

	$titles = array();

	foreach ( $movie['alternative_titles']['titles'] as $item ) {
		if ( ! empty( $item['title'] ) ) {
			$titles[] = $item['title'];
		}
	}

	update_post_meta( $post_id, 'alternative_titles', array_values( array_unique( $titles ) ) );
}

function ms_save_movie_production_companies( $post_id, $movie ) {
	if ( empty( $movie['production_companies'] ) || ! is_array( $movie['production_companies'] ) ) {
		return;
	}

	$companies = array();

	foreach ( $movie['production_companies'] as $company ) {
		if ( ! empty( $company['name'] ) ) {
			$companies[] = $company['name'];
		}
	}

	update_post_meta( $post_id, 'production_companies', $companies );
}

function ms_save_movie_reviews( $post_id, $movie ) {
	if ( empty( $movie['reviews']['results'] ) || ! is_array( $movie['reviews']['results'] ) ) {
		return;
	}

	$reviews = array();

	foreach ( $movie['reviews']['results'] as $review ) {
		$reviews[] = array(
			'author'  => $review['author'] ?? '',
			'content' => $review['content'] ?? '',
		);
	}

	update_post_meta( $post_id, 'reviews', $reviews );
}


function ms_save_movie_similar( $post_id, $movie ) {
	if ( empty( $movie['similar']['results'] ) || ! is_array( $movie['similar']['results'] ) ) {
		return;
	}

	$similar_movies = array();

	foreach ( $movie['similar']['results'] as $similar ) {
		$similar_movies[] = array(
			'tmdb_id'      => $similar['id'] ?? '',
			'title'        => $similar['title'] ?? '',
			'release_date' => $similar['release_date'] ?? '',
			'poster_path'  => $similar['poster_path'] ?? '',
		);
	}

	update_post_meta( $post_id, 'similar_movies', $similar_movies );
}

function ms_sync_movie_genres( $post_id, $movie ) {
	if ( empty( $movie['genres'] ) || ! is_array( $movie['genres'] ) ) {
		return;
	}

	$term_ids = array();

	foreach ( $movie['genres'] as $genre ) {
		if ( empty( $genre['name'] ) ) {
			continue;
		}

		$term = term_exists( $genre['name'], 'genre' );

		if ( ! $term ) {
			$created = wp_insert_term(
				$genre['name'],
				'genre',
				array(
					'slug' => sanitize_title( $genre['name'] ),
				)
			);

			if ( is_wp_error( $created ) ) {
				continue;
			}

			$term_id = (int) $created['term_id'];
			update_term_meta( $term_id, 'tmdb_genre_id', $genre['id'] ?? '' );
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

function ms_sync_movie_cast( $movie_post_id, $movie_tmdb_id ) {
	$data = ms_tmdb_get(
		'movie/' . $movie_tmdb_id . '/credits',
		array(
			'language' => 'en-US',
		)
	);

	if ( empty( $data['cast'] ) || ! is_array( $data['cast'] ) ) {
		return;
	}

	$related_actor_ids = array();
	// only 5 actors for faster testing
	$cast = array_slice( $data['cast'], 0, 5 );

	foreach ( $cast as $cast_member ) {
		$actor_post_id = ms_upsert_actor_from_cast( $cast_member, $movie_post_id );

		if ( ! $actor_post_id ) {
			continue;
		}

		$related_actor_ids[] = $actor_post_id;
	}

	update_post_meta( $movie_post_id, 'related_actor_ids', array_values( array_unique( $related_actor_ids ) ) );
}

;?>