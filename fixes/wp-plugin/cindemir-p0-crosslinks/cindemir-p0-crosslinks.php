<?php
/**
 * Plugin Name: Cindemir P0 Crosslinks
 * Description: Appends contextual dofollow links from cindemir.av.tr to cindemirlaw.com P0 money pages (and hub lists). Works with classic content and Elementor output via the_content.
 * Version: 1.0.0
 * Author: Cindemir Law Office
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * HTML blocks keyed by post/page ID on cindemir.av.tr.
 */
function cindemir_p0_crosslink_map()
{
    $m = '<!-- cindemirlaw-p0-link -->';
    return array(
        // Company (TR)
        3331 => $m . '<p>Yabancı yatırımcılar için süreç özeti (İngilizce): <a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">yabancılar için Türkiye’de şirket kurulumu</a>.</p>',
        // Deportation (TR)
        1843 => $m . '<p>English overview for international clients: <a href="https://cindemirlaw.com/deportation-law-in-turkey/">deportation law in Turkey</a>.</p>',
        // Deportation (EN mirror)
        377 => $m . '<p>For the full English guide on our international site, see <a href="https://cindemirlaw.com/deportation-law-in-turkey/">deportation law in Turkey</a>.</p>',
        // Divorce (EN)
        404 => $m . '<p>Related guide: <a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">uncontested / consensual divorce in Turkey</a>.</p>',
        // Debt (EN)
        336 => $m . '<p>International clients: <a href="https://cindemirlaw.com/debt-recovery-in-turkey/">debt recovery in Turkey</a>.</p>',
        // Criminal record (EN)
        1492 => $m . '<p>Step-by-step English guide: <a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">getting a criminal record certificate in Turkey</a>.</p>',
        // Hubs
        462 => $m . '<p><strong>Uluslararası / İngilizce bilgilendirme:</strong></p><ul>'
            . '<li><a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">Company formation for foreigners</a></li>'
            . '<li><a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">Uncontested divorce in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/deportation-law-in-turkey/">Deportation law in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/debt-recovery-in-turkey/">Debt recovery in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">Criminal record certificate in Turkey</a></li>'
            . '</ul>',
        126 => $m . '<p><strong>International English guides:</strong></p><ul>'
            . '<li><a href="https://cindemirlaw.com/opening-a-company-in-turkey-for-foreigners/">Company formation for foreigners</a></li>'
            . '<li><a href="https://cindemirlaw.com/consensual-divorce-in-turkey-uncontested-divorce/">Uncontested divorce in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/deportation-law-in-turkey/">Deportation law in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/debt-recovery-in-turkey/">Debt recovery in Turkey</a></li>'
            . '<li><a href="https://cindemirlaw.com/getting-criminal-record-in-turkey/">Criminal record certificate in Turkey</a></li>'
            . '</ul>',
        438 => $m . '<p>Uluslararası danışanlar için İngilizce sitemiz: <a href="https://cindemirlaw.com/about-us/">Cindemir Law Office — About us</a>.</p>',
    );
}

function cindemir_p0_crosslinks_append($content)
{
    if (is_admin()) {
        return $content;
    }
    if (!is_singular()) {
        return $content;
    }
    // Avoid feeds / excerpts where loop flags differ
    if (function_exists('is_feed') && is_feed()) {
        return $content;
    }

    $id = (int) get_the_ID();
    $map = cindemir_p0_crosslink_map();
    if ($id <= 0 || !isset($map[$id])) {
        return $content;
    }

    // Idempotent: already present in rendered HTML (classic body or prior filter)
    if (strpos($content, 'cindemirlaw-p0-link') !== false) {
        return $content;
    }

    return $content . "\n" . $map[$id] . "\n";
}

add_filter('the_content', 'cindemir_p0_crosslinks_append', 25);

// Elementor sometimes bypasses late the_content on certain templates; also hook builder content.
add_action('elementor/frontend/after_render', function ($element = null) {
    // no-op placeholder — primary path is the_content
}, 10, 1);

add_filter('elementor/frontend/builder_content_data', function ($data, $post_id) {
    return $data;
}, 10, 2);
