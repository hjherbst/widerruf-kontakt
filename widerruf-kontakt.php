<?php
/**
 * Plugin Name: Widerruf & Kontakt
 * Plugin URI: https://github.com/hjherbst/widerruf-kontakt
 * Description: Datenschutzkonformes Widerrufsformular und Kontaktformular als native Gutenberg-Blöcke. Ideal für die gesetzliche Widerrufsrecht-Pflicht bei Waren, digitalen Produkten und Verträgen. Mit SMTP-Setup und automatischen Eingangsbestätigungen. Free, no account required.
 * Version: 1.0.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: Hans-Jürgen Herbst
 * Author URI: https://gutenblock.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: widerruf-kontakt
 * Domain Path: /languages
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WK_VERSION',  '1.0.0' );
define( 'WK_FILE',     __FILE__ );
define( 'WK_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WK_URL',      plugin_dir_url( __FILE__ ) );

require_once WK_PATH . 'vendor/plugin-update-checker/load-v5p6.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

require_once WK_PATH . 'inc/class-i18n.php';
require_once WK_PATH . 'inc/class-mailer.php';
require_once WK_PATH . 'inc/class-mail-presets.php';
require_once WK_PATH . 'inc/class-mail-settings.php';
require_once WK_PATH . 'inc/class-revocation-form.php';
require_once WK_PATH . 'inc/class-contact-form.php';
require_once WK_PATH . 'inc/class-admin.php';

/**
 * Initialise the plugin.
 */
function wk_init() {
	load_plugin_textdomain( 'widerruf-kontakt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	$rf = new WK_Revocation_Form();
	$cf = new WK_Contact_Form();

	$rf->init();
	$cf->init();

	add_action( 'rest_api_init', array( $rf, 'register_routes' ) );
	add_action( 'rest_api_init', array( $cf, 'register_routes' ) );

	if ( is_admin() ) {
		( new WK_Admin() )->init();
		( new WK_Mail_Settings() )->init();
	}
}
add_action( 'plugins_loaded', 'wk_init' );

/**
 * Plugin Update Checker against the GitHub repo.
 * Repo URL is set to a placeholder until the real repo exists.
 */
function wk_init_update_checker() {
	$repo_url = 'https://github.com/hjherbst/widerruf-kontakt/';
	$checker  = PucFactory::buildUpdateChecker( $repo_url, WK_FILE, 'widerruf-kontakt' );
	$checker->setBranch( 'main' );
	$checker->getVcsApi()->enableReleaseAssets();
}
add_action( 'plugins_loaded', 'wk_init_update_checker', 5 );
