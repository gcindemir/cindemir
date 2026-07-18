<?php
/**
 * Upload to: public_html/_fix/heal.php (create _fix folder first)
 * Open: https://cindemirlaw.com/_fix/heal.php?key=seo-pack-2026
 */
header( 'Content-Type: text/plain; charset=utf-8' );

if ( ( $_GET['key'] ?? '' ) !== 'seo-pack-2026' ) {
	http_response_code( 403 );
	echo "Forbidden\n";
	exit;
}

$root = dirname( __DIR__ );
$mu   = $root . '/wp-content/mu-plugins';

echo "Cindemir heal v2\nroot=$root\n\n";

if ( ! is_dir( $mu ) ) {
	// Maybe user renamed folder.
	foreach ( glob( $root . '/wp-content/mu-plugins*' ) as $d ) {
		echo "found: $d\n";
	}
	echo "ERROR: mu-plugins not found\n";
	exit;
}

$names = glob( $mu . '/cindemir*.php' ) ?: array();
foreach ( $names as $path ) {
	$off = $path . '.off';
	if ( @rename( $path, $off ) ) {
		echo "off: " . basename( $path ) . "\n";
	}
}

$base = 'https://raw.githubusercontent.com/gcindemir/cindemir/cursor/cindemirlaw-seo-tasks-d204/fixes/mu-plugins/';
$pack = array(
	'cindemir-seo-fixes.php'         => 40000,
	'cindemir-contact-fixes.php'     => 20000,
	'cindemir-expose-yoast-meta.php' => 2000,
	'cindemir-purge-cache.php'       => 500,
);

foreach ( $pack as $name => $min ) {
	$body = @file_get_contents( $base . $name, false, stream_context_create( array(
		'http' => array( 'timeout' => 60, 'header' => "User-Agent: CindemirHeal/2\r\n" ),
	) ) );
	$n = is_string( $body ) ? strlen( $body ) : 0;
	if ( $n < $min ) {
		echo "FAIL $name ($n bytes)\n";
		continue;
	}
	file_put_contents( $mu . '/' . $name, $body );
	echo "OK $name ($n bytes)\n";
}

echo "\nDONE — open wp-admin\n";
@unlink( __FILE__ );
