<?php

function edd_fes_pu_products( $author_id = 0 ) {

	if( empty( $author_id ) ) {
		$author_id = get_current_user_id();
	}

	$query = new WP_Query( array(
		'author'      => $author_id,
		'post_type'   => 'download',
		'post_status' => 'publish',
		'number'      => -1
	) );

	return $query->posts;
}