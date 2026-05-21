<?php

/**
 * Temporary smoke test — confirms the configured AI provider is reachable.
 *
 * Hooks into `admin_notices` so the result is visible on every admin screen.
 * Removed at the start of Section 3 once the real ability replaces it.
 */
function wp_ai_workshop_test_ai_connection() {
	// Restrict to admins; we don't want every editor seeing the smoke test.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// `wp_ai_client_prompt()` returns a builder object; calling
	// `->generate_text()` is what makes the actual provider request. The
	// result is a string on success or a WP_Error on failure (bad key, rate
	// limit, network error, etc.).
	$response = wp_ai_client_prompt(
		'Say hello to a WordPress workshop audience in exactly one sentence.'
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
add_action( 'admin_notices', 'wp_ai_workshop_test_ai_connection' );
