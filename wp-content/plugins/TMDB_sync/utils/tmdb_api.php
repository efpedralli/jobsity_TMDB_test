<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

function ms_tmdb_get( $endpoint, $params = array() ) {
	$base_url = 'https://api.themoviedb.org/3/';
	$endpoint = ltrim( $endpoint, '/' );

	$url = $base_url . $endpoint;

	if ( ! empty( $params ) ) {
		$url = add_query_arg( $params, $url );
	}

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . TMDB_API_TOKEN,
				'Accept'        => 'application/json',
			),
			'timeout' => 20,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( 200 !== $code || empty( $body ) ) {
		return false;
	}

	$data = json_decode( $body, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return false;
	}

	return $data;
};