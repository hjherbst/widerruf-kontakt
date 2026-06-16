<?php
/**
 * WK_I18n – central language helpers.
 *
 * EN is the default. DE is used only when the site locale starts with "de".
 * No client-side heuristics, no umlaut checks.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the site is currently running a German locale.
 *
 * @return bool
 */
function wk_is_de() {
	$locale = strtolower( (string) get_locale() );
	return 0 === strpos( $locale, 'de' );
}
