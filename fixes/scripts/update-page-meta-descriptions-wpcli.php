<?php
/**
 * WP-CLI eval-file script: update Yoast meta descriptions for 14 pages.
 *
 * Run from WordPress root:
 *   wp eval-file fixes/scripts/update-page-meta-descriptions-wpcli.php
 */

if ( ! function_exists( 'update_post_meta' ) ) {
	fwrite( STDERR, "Must run via WP-CLI inside WordPress.\n" );
	exit( 1 );
}

$json_path = dirname( __DIR__ ) . '/meta-descriptions/pages-14.json';
if ( ! is_readable( $json_path ) ) {
	fwrite( STDERR, "Missing: $json_path\n" );
	exit( 1 );
}

$pages = json_decode( file_get_contents( $json_path ), true );
if ( ! is_array( $pages ) ) {
	fwrite( STDERR, "Invalid JSON.\n" );
	exit( 1 );
}

foreach ( $pages as $row ) {
	$id       = (int) $row['id'];
	$metadesc = $row['metadesc'];
	$len      = function_exists( 'mb_strlen' ) ? mb_strlen( $metadesc ) : strlen( $metadesc );

	if ( $len < 110 || $len > 160 ) {
		WP_CLI::warning( "Page $id: length $len (target 110–160)" );
	}

	$result = update_post_meta( $id, '_yoast_wpseo_metadesc', $metadesc );
	if ( false === $result ) {
		WP_CLI::warning( "Page $id: meta unchanged or failed." );
	} else {
		WP_CLI::success( "Page $id ($len chars): {$row['url']}" );
	}
}

WP_CLI::log( 'Done. Clear Yoast/sitemap cache if needed.' );
