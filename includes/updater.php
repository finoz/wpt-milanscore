<?php
/**
 * fnz wpt milanscore – GitHub auto-updater
 */

defined( 'ABSPATH' ) || exit;

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Fetch the latest release from the GitHub API.
 * Result is cached for 6 hours via a WP transient.
 *
 * @return array|null  Decoded release object, or null on failure.
 */
function fnz_theme_updater_get_release(): ?array {

	$repo      = FNZ_THEME_GITHUB_REPO;
	$cache_key = 'fnz_theme_gh_release_' . md5( $repo );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) return $cached ?: null;

	$args = [
		'timeout' => 10,
		'headers' => [ 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) ],
	];

	if ( defined( 'FNZ_THEME_GITHUB_TOKEN' ) ) {
		$args['headers']['Authorization'] = 'Bearer ' . FNZ_THEME_GITHUB_TOKEN;
	}

	$response = wp_remote_get(
		"https://api.github.com/repos/{$repo}/releases/latest",
		$args
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_transient( $cache_key, [], HOUR_IN_SECONDS ); // negative cache
		return null;
	}

	$release = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $release['tag_name'] ) ) return null;

	set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );
	return $release;
}

/**
 * Return the download URL for the release zip.
 * Prefers an attached .zip asset; falls back to GitHub's auto-generated source zip.
 */
function fnz_theme_updater_zip_url( array $release ): string {

	foreach ( $release['assets'] ?? [] as $asset ) {
		if ( str_ends_with( $asset['name'], '.zip' ) ) {
			return $asset['browser_download_url'];
		}
	}

	return 'https://github.com/' . FNZ_THEME_GITHUB_REPO . '/archive/refs/tags/' . $release['tag_name'] . '.zip';
}

// ── Hook 1: inject update into WP's theme update transient ───────────────────

add_filter( 'pre_set_site_transient_update_themes', static function ( $transient ) {

	if ( empty( $transient->checked ) ) return $transient;

	$release = fnz_theme_updater_get_release();
	if ( ! $release ) return $transient;

	$slug    = FNZ_THEME_SLUG;
	$latest  = ltrim( $release['tag_name'], 'v' );
	$current = $transient->checked[ $slug ] ?? FNZ_THEME_VERSION;

	if ( version_compare( $latest, $current, '>' ) ) {
		$transient->response[ $slug ] = [
			'theme'       => $slug,
			'new_version' => $latest,
			'url'         => 'https://github.com/' . FNZ_THEME_GITHUB_REPO,
			'package'     => fnz_theme_updater_zip_url( $release ),
		];
	}

	return $transient;
} );

// ── Hook 2: rename extracted folder after install ─────────────────────────────
//
// GitHub's auto-generated zip contains a folder named "wpt-milanscore-v1.2.3/"
// WP would install it under that name, breaking the theme activation.
// We rename it to 'fnz-wpt-milanscore/' right after extraction.

add_filter( 'upgrader_post_install', static function ( $response, array $hook_extra, array $result ) {

	if ( ( $hook_extra['theme'] ?? '' ) !== FNZ_THEME_SLUG ) {
		return $response;
	}

	global $wp_filesystem;

	$dest = get_theme_root() . '/' . FNZ_THEME_SLUG;

	if ( trailingslashit( $result['destination'] ) !== trailingslashit( $dest ) ) {
		$wp_filesystem->move( $result['destination'], $dest, true );
		$result['destination'] = $dest;
	}

	return $result;

}, 10, 3 );
