<?php
/**
 * WK_Mail_Presets – SMTP server presets for the guided email setup.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WK_Mail_Presets {

	/**
	 * SMTP server presets keyed by slug.
	 *
	 * @return array
	 */
	public static function servers() {
		return array(
			'brevo'     => array( 'host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls' ),
			'ionos'     => array( 'host' => 'smtp.ionos.de',        'port' => 587, 'encryption' => 'tls' ),
			'strato'    => array( 'host' => 'smtp.strato.de',       'port' => 465, 'encryption' => 'ssl' ),
			'allinkl'   => array( 'host' => 'smtp.all-inkl.com',    'port' => 587, 'encryption' => 'tls' ),
			'hostinger' => array( 'host' => 'smtp.hostinger.com',   'port' => 587, 'encryption' => 'tls' ),
			'google'    => array( 'host' => 'smtp.gmail.com',       'port' => 587, 'encryption' => 'tls' ),
			'microsoft' => array( 'host' => 'smtp.office365.com',   'port' => 587, 'encryption' => 'tls' ),
			'other'     => array( 'host' => '',                     'port' => 587, 'encryption' => 'tls' ),
		);
	}

	/**
	 * Get a single preset by slug.
	 *
	 * @param string $slug Preset slug.
	 * @return array|null
	 */
	public static function get_server( $slug ) {
		$servers = self::servers();
		return isset( $servers[ $slug ] ) ? $servers[ $slug ] : null;
	}

	/**
	 * Mailbox provider options for the dropdown.
	 *
	 * @return array slug => [ 'label', 'hint' ]
	 */
	public static function mailbox_providers() {
		$de = wk_is_de();
		return array(
			'ionos'     => array(
				'label' => 'IONOS',
				'hint'  => $de ? 'Benutzername ist die vollständige E-Mail-Adresse. Passwort = dein E-Mail-Postfach-Passwort.' : 'Username is the full email address. Password = your mailbox password.',
			),
			'strato'    => array(
				'label' => 'Strato',
				'hint'  => $de ? 'Benutzername ist die vollständige E-Mail-Adresse. Passwort = dein E-Mail-Postfach-Passwort.' : 'Username is the full email address. Password = your mailbox password.',
			),
			'allinkl'   => array(
				'label' => 'All-Inkl',
				'hint'  => $de ? 'Benutzername ist die vollständige E-Mail-Adresse. Passwort = dein E-Mail-Postfach-Passwort.' : 'Username is the full email address. Password = your mailbox password.',
			),
			'hostinger' => array(
				'label' => 'Hostinger',
				'hint'  => $de ? 'Benutzername ist die vollständige E-Mail-Adresse. Passwort = dein E-Mail-Postfach-Passwort.' : 'Username is the full email address. Password = your mailbox password.',
			),
			'google'    => array(
				'label' => 'Google Workspace / Gmail',
				'hint'  => $de ? 'Wichtig: Hier ist ein App-Passwort nötig. Erstelle es in deinem Google-Konto unter „Sicherheit → App-Passwörter".' : 'Important: an app password is required here. Create it in your Google account under "Security → App passwords".',
			),
			'microsoft' => array(
				'label' => 'Microsoft 365 / Outlook',
				'hint'  => $de ? 'Benutzername ist die vollständige E-Mail-Adresse. SMTP-AUTH muss im Microsoft-Admincenter aktiviert sein.' : 'Username is the full email address. SMTP AUTH must be enabled in the Microsoft admin centre.',
			),
			'other'     => array(
				'label' => $de ? 'Anderer Anbieter' : 'Other provider',
				'hint'  => $de ? 'Nutze bitte die erweiterten SMTP-Einstellungen weiter unten.' : 'Please use the advanced manual SMTP settings below.',
			),
		);
	}

	/**
	 * Human-readable summary for a preset (host · port · encryption).
	 *
	 * @param string $slug Preset slug.
	 * @return string
	 */
	public static function summary( $slug ) {
		$server = self::get_server( $slug );
		if ( ! $server || '' === $server['host'] ) {
			return '';
		}
		$enc = strtoupper( 'none' === $server['encryption'] ? 'none' : $server['encryption'] );
		return sprintf( '%s · Port %d · %s', $server['host'], $server['port'], $enc );
	}
}
