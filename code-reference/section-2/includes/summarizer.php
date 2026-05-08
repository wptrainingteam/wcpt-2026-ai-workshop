<?php

function wcpt_test_ai_connection() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$response = wp_ai_client_prompt(
		'Say hello to the WordCamp Portugal 2026 workshop attendees in exactly one sentence.'
	)->generate_text();

	if ( is_wp_error( $response ) ) {
		printf(
			'<div class="notice notice-error"><p>AI Error: %s</p></div>',
			esc_html( $response->get_error_message() )
		);
		return;
	}

	printf(
		'<div class="notice notice-success"><p>%s</p></div>',
		esc_html( $response )
	);
}
add_action( 'admin_notices', 'wcpt_test_ai_connection' );
