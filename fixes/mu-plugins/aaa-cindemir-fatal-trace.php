<?php
/**
 * Temporary front fatality tracer — remove after WhatsApp fix is verified.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
register_shutdown_function(
	static function () {
		$err = error_get_last();
		if ( ! $err || empty( $err['type'] ) ) {
			return;
		}
		$fatal = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );
		if ( ! in_array( (int) $err['type'], $fatal, true ) ) {
			return;
		}
		$dir = WP_CONTENT_DIR . '/uploads';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$file = $dir . '/cindemir-last-fatal.txt';
		$line = gmdate( 'c' ) . ' ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line'] . "\n";
		@file_put_contents( $file, $line, FILE_APPEND );
	}
);
