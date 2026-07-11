<?php
/**
 * One-time: create WPML Chinese translation for Contacts page (EN post ID 20).
 * Delete this file after successful run.
 */
define( 'CINDEMIR_ZH_SETUP_KEY', 'wpml-setup-zh-2026' );

if ( ! isset( $_GET['key'] ) || $_GET['key'] !== CINDEMIR_ZH_SETUP_KEY ) {
	http_response_code( 403 );
	exit( 'Forbidden' );
}

require_once __DIR__ . '/wp-load.php';

header( 'Content-Type: text/plain; charset=utf-8' );

$source_id = 20;
$trid      = 20;
$lang      = 'zh-hans';
$source    = get_post( $source_id );

if ( ! $source ) {
	exit( "ERROR: source post {$source_id} not found\n" );
}

global $wpdb;

$existing = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page'
		 WHERE t.trid = %d AND t.language_code = %s AND p.post_status = 'publish'",
		$trid,
		$lang
	)
);

if ( $existing ) {
	echo "ALREADY_EXISTS post_id={$existing}\n";
	echo "URL: " . get_permalink( (int) $existing ) . "\n";
	exit;
}

$content = $source->post_content;

$replacements = array(
	"heading='Contact Us'"              => "heading='联系我们'",
	"button='Submit'"                   => "button='立即发送'",
	"sent='Your message has been sent!'" => "sent='您的消息已发送！'",
	"label='Name'"                      => "label='姓名'",
	"label='E-Mail'"                    => "label='电子邮箱'",
	"label='Phone'"                     => "label='电话号码'",
	"label='Message'"                   => "label='留言'",
	'<h4>Cindemir Hukuk Bürosu / Cindemir Law Office</h4>' => '<h4>辛德米尔律师事务所 / Cindemir Law Office</h4>',
	'<strong>Adress:</strong>'          => '<strong>地址：</strong>',
	'<strong>Email:</strong>'            => '<strong>电子邮箱：</strong>',
	'<h4>Registered Electronic Mail (REM)</h4>' => '<h4>注册电子信箱 (KEP)</h4>',
	'<strong>Fax:</strong>'              => '<strong>传真：</strong>',
	'<strong>Phone:</strong>'            => '<strong>电话：</strong>',
	'<strong>Check İstanbul Ritim Residences in English </strong>' => '<strong>查看伊斯坦布尔 Ritim 住宅区（英文）</strong>',
	'<strong>Whatsapp:</strong>'         => '<strong>WhatsApp：</strong>',
);

foreach ( $replacements as $from => $to ) {
	$content = str_replace( $from, $to, $content );
}

$new_post = array(
	'post_title'   => '联系我们',
	'post_name'    => 'contacts',
	'post_content' => $content,
	'post_status'  => 'publish',
	'post_type'    => 'page',
	'post_author'  => $source->post_author,
	'menu_order'   => $source->menu_order,
	'comment_status' => $source->comment_status,
	'ping_status'    => $source->ping_status,
);

$new_id = wp_insert_post( $new_post, true );

if ( is_wp_error( $new_id ) ) {
	exit( 'ERROR wp_insert_post: ' . $new_id->get_error_message() . "\n" );
}

$meta = get_post_meta( $source_id );
foreach ( $meta as $key => $values ) {
	if ( strpos( $key, '_edit_' ) === 0 || $key === '_wp_old_slug' ) {
		continue;
	}
	foreach ( $values as $value ) {
		$val = maybe_unserialize( $value );
		if ( is_string( $val ) ) {
			foreach ( $replacements as $from => $to ) {
				$val = str_replace( $from, $to, $val );
			}
		}
		update_post_meta( $new_id, $key, $val );
	}
}

do_action(
	'wpml_set_element_language_details',
	array(
		'element_id'           => $new_id,
		'element_type'         => 'post_page',
		'trid'                 => $trid,
		'language_code'        => $lang,
		'source_language_code' => 'en',
	)
);

if ( function_exists( 'icl_object_id' ) ) {
	global $sitepress;
	if ( $sitepress ) {
		$sitepress->set_element_language_details( $new_id, 'post_page', $trid, $lang, 'en' );
	}
}

clean_post_cache( $new_id );
wp_cache_flush();

echo "SUCCESS\n";
echo "new_post_id={$new_id}\n";
echo "title=" . get_the_title( $new_id ) . "\n";
echo "permalink=" . get_permalink( $new_id ) . "\n";
echo "zh_url=https://cindemirlaw.com/contacts/?lang=zh-hans\n";
