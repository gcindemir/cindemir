<?php
/**
 * Cindemir emergency heal — upload to public_html root (NOT mu-plugins).
 * Visit: https://cindemirlaw.com/heal-cindemir.php?key=seo-pack-2026
 * Deletes itself after success.
 */
header( 'Content-Type: text/plain; charset=utf-8' );

$key = isset( $_GET['key'] ) ? (string) $_GET['key'] : '';
if ( 'seo-pack-2026' !== $key ) {
	http_response_code( 403 );
	echo "Forbidden. Add ?key=seo-pack-2026\n";
	exit;
}

$root = __DIR__;
$mu   = $root . '/wp-content/mu-plugins';

if ( ! is_dir( $mu ) ) {
	echo "ERROR: mu-plugins folder not found: $mu\n";
	exit;
}

$branch = 'cursor/cindemirlaw-seo-tasks-d204';
$base   = 'https://raw.githubusercontent.com/gcindemir/cindemir/' . $branch . '/fixes/mu-plugins/';

$disable = array(
	'cindemir-seo-fixes.php',
	'cindemir-contact-fixes.php',
	'cindemir-expose-yoast-meta.php',
	'cindemir-purge-cache.php',
	'cindemir-force-upgrade.php',
	'cindemir-remote-deploy.php',
);

echo "=== Cindemir emergency heal ===\n\n";

// 1) Disable all current cindemir mu-plugins.
foreach ( $disable as $name ) {
	$path = $mu . '/' . $name;
	if ( ! file_exists( $path ) ) {
		echo "skip (missing): $name\n";
		continue;
	}
	$off = $path . '.off-' . gmdate( 'Ymd-His' );
	if ( @rename( $path, $off ) ) {
		echo "disabled: $name -> " . basename( $off ) . "\n";
	} else {
		echo "WARN: could not rename $name\n";
	}
}

// 2) Download known-good pack from GitHub.
$files = array(
	'cindemir-seo-fixes.php'         => 40000,
	'cindemir-contact-fixes.php'     => 20000,
	'cindemir-expose-yoast-meta.php' => 2000,
	'cindemir-purge-cache.php'       => 500,
);

$ok = 0;
foreach ( $files as $name => $min ) {
	$url = $base . rawurlencode( $name );
	$ctx = stream_context_create(
		array(
			'http' => array(
				'timeout' => 60,
				'header'  => "User-Agent: CindemirHeal/1.0\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $ctx );
	$size = is_string( $body ) ? strlen( $body ) : 0;
	if ( $size < $min ) {
		echo "FAIL download $name ($size bytes from $url)\n";
		continue;
	}
	$dest = $mu . '/' . $name;
	if ( false === @file_put_contents( $dest, $body ) ) {
		echo "FAIL write $name\n";
		continue;
	}
	echo "OK $name ($size bytes)\n";
	$ok++;
}

if ( $ok < count( $files ) ) {
	echo "\nPARTIAL — wp-admin may still be broken. Re-run after fixing network.\n";
	exit;
}

// 3) Trigger upgrade endpoint (optional).
$upgrade = 'https://cindemirlaw.com/?cindemir_upgrade=seo-pack-2026';
@file_get_contents( $upgrade, false, stream_context_create( array( 'http' => array( 'timeout' => 20 ) ) ) );
echo "\nTriggered upgrade URL.\n";

// 4) Self-delete.
$self = __FILE__;
echo "\nDONE. Test: https://cindemirlaw.com/wp-admin/\n";
@unlink( $self );
echo "heal script removed.\n";
