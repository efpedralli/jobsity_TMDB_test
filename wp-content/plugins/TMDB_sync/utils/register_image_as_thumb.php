<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

function ms_attach_image_from_url( $image_url, $post_id ) {
	if ( empty( $image_url ) || empty( $post_id ) ) {
		return false;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $image_url );

	if ( is_wp_error( $tmp ) ) {
		return false;
	}

	$file_array = array(
		'name'     => basename( parse_url( $image_url, PHP_URL_PATH ) ),
		'tmp_name' => $tmp,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return false;
	}

	set_post_thumbnail( $post_id, $attachment_id );

	return $attachment_id;
};

;?>