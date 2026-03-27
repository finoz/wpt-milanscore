<?php
/**
 * fnz wpt milanscore — functions.php
 * Nei block theme il parent non va enqueued:
 * i suoi stili vengono da theme.json e dai block stylesheets.
 */

// ── Auto-updater ──────────────────────────────────────────────────────────────

define( 'FNZ_THEME_VERSION',     '1.0.0' );
define( 'FNZ_THEME_SLUG',        'fnz-wpt-milanscore' );
define( 'FNZ_THEME_GITHUB_REPO', 'finoz/wpt-milanscore' );

require_once get_stylesheet_directory() . '/includes/updater.php';

add_action( 'wp_enqueue_scripts', 'fnz_enqueue_map' );
function fnz_enqueue_map() {
    //if ( ! is_page( 'nome-pagina' ) ) return;

    wp_enqueue_script(
        'fnz-map-data',
        get_stylesheet_directory_uri() . '/js/map-data.js',
        [],
        '1.1.0',
        true
    );

    wp_enqueue_script(
        'fnz-map',
        get_stylesheet_directory_uri() . '/js/map.js',
        [ 'fnz-map-data' ],
        '1.1.0',
        true
    );

    wp_enqueue_script(
        'google-maps-api',
        'https://maps.googleapis.com/maps/api/js?key=AIzaSyBiAjiq2PvmW2Iybk3yB7Rx4Egzy3q3huo&callback=initMap&loading=async',
        [ 'fnz-map' ],
        null,
        true
    );
}