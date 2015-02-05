<?php

function edd_fes_pu_products( $author_id = 0 ) {

	if( empty( $author_id ) ) {
		$author_id = get_current_user_id();
	}

	$query = new WP_Query( array(
		'author'      => $author_id,
		'post_type'   => 'download',
		'post_status' => 'publish',
		'nopaging'    => true
	) );

	return $query->posts;
}

function edd_fes_pu_has_pending_emails( $author_id = 0 ) {

	if( empty( $author_id ) ) {
		$author_id = get_current_user_id();
	}

	return (bool) edd_fes_pu_pending_emails_count( $author_id );

}

function edd_fes_pu_pending_emails_count( $author_id = 0 ) {

	if( empty( $author_id ) ) {
		$author_id = get_current_user_id();
	}

	$query = new WP_Query( array(
		'author'      => $author_id,
		'post_type'   => 'edd_pup_email',
		'post_status' => 'draft',
		'nopaging'    => true,
		'fields'      => 'ids'
	) );

	return $query->post_count;

}

function edd_fes_pu_get_email_tags() {

	$tags = array();

	foreach( edd_get_email_tags() as $tag ) {

		$tags[ $tag['tag'] ] = '{' . $tag['tag'] . '}';

	}

	return $tags;
}

function edd_fes_pu_get_enabled_email_tags() {

	$tags    = edd_fes_pu_get_email_tags();
	$enabled = edd_get_option( 'fes_pu_tags', array() );

	foreach( $tags as $key => $tag ) {

		if( ! array_key_exists( $key, $enabled ) ) {
			unset( $tags[ $key ] );
		}

	}

	return $tags;
}