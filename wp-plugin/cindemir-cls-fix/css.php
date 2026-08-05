<?php
/**
 * Patched Debloat CSS proxy (bypasses WP Rocket page cache).
 * Usage: /wp-content/plugins/cindemir-cls-fix/css.php?f=HASH.css
 */
$file = isset( $_GET['f'] ) ? basename( (string) $_GET['f'] ) : '';
if ( ! $file || ! preg_match( '/^[a-f0-9]+\.css$/', $file ) ) {
	http_response_code( 404 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Not found';
	exit;
}

// Locate wp-content/cache relative to this plugin file.
$css_path = dirname( __DIR__, 2 ) . '/cache/debloat/css/' . $file;
if ( ! is_readable( $css_path ) ) {
	http_response_code( 404 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	echo 'Missing';
	exit;
}

$css   = file_get_contents( $css_path );
$pairs = array(
	'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg'                   => 'https://cindemirlaw.com/wp-content/uploads/2020/10/540664430.jpg.webp',
	'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg'                 => 'https://cindemirlaw.com/wp-content/uploads/2026/07/team-4person.jpg.webp',
	'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg' => 'https://cindemirlaw.com/wp-content/uploads/2020/06/cropped-logoicon-1-1-300x300.jpg.webp',
);
foreach ( $pairs as $jpg => $webp ) {
	$token = '%%CINDEMIR_' . md5( $webp ) . '%%';
	$css   = str_replace( $webp, $token, $css );
	$css   = str_replace( $jpg, $webp, $css );
	$css   = str_replace( $token, $webp, $css );
}
$css = preg_replace( '/font-display\s*:\s*(auto|swap|block|fallback)\b/i', 'font-display:optional', $css );
// Ensure every @font-face has font-display:optional (Debloat/Enfold often omit it).
$css = preg_replace_callback(
	'/@font-face\s*\{[^}]*\}/i',
	static function ( $m ) {
		$block = $m[0];
		if ( stripos( $block, 'font-display' ) !== false ) {
			return preg_replace( '/font-display\s*:\s*[a-z]+/i', 'font-display:optional', $block );
		}
		return preg_replace( '/\{/', '{font-display:optional;', $block, 1 );
	},
	$css
);

header( 'Content-Type: text/css; charset=UTF-8' );
header( 'Cache-Control: public, max-age=604800' );
header( 'X-Cindemir-CSS: patched' );
echo $css;
