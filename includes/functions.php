<?php

/**
 * Pretty print variable.
 *
 * @param  mixed $data Variable.
 */
function homecfcc_debug( $data = array(), $exit = false ) {
	echo '<pre>';
	print_r( $data ); // phpcs:ignore
	echo '</pre>';

	if ( $exit ) {
		exit;
	}
}

/**
 * Log
 */
function homecfcc_log( $message, $context = [], $level = 'info' ) {
	if ( empty( $context ) ) {
		$context = [ 
			'Cron' => (int) wp_doing_cron(),
			'Ajax' => (int) wp_doing_ajax()
		];
	}

	do_action(
		'swpl_log',
		'HomerunnerForGuesty',
		$message,
		$context,
		$level
	);
}