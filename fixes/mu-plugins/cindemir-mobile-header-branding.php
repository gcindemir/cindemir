<?php
/**
 * Plugin Name: Cindemir Mobile Header Branding
 * Description: Fallback site-name in header (SEO pack also injects branding).
 * Version: 1.0.1
 * Author: Cindemir Law Office
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( defined( 'CINDEMIR_SEO_FIXES_LOADED' ) ) { return; } // pack handles it
if ( defined( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED' ) ) { return; }
define( 'CINDEMIR_MOBILE_HEADER_BRANDING_LOADED', true );
add_action( 'wp_head', function () {
	if ( is_admin() ) { return; }
	echo '<style id="cindemir-mobile-brand">@media(max-width:989px){#header .logo a{display:inline-flex!important;align-items:center;gap:8px}#header .logo img{max-height:38px!important;max-width:38px!important}#header .logo a::after{content:"Cindemir Law Office";font-size:13px;font-weight:600;line-height:1.2;color:#336666;max-width:170px}}</style>';
}, 50 );
