<?php
/**
 * WK_Mailer – wp_mail wrapper with optional SMTP transport.
 *
 * Mirrors GutenBlock Pro's contact-form mailer; all option keys use the wk_*
 * prefix so this plugin coexists with GutenBlock on the same site without
 * sharing or overwriting any database options.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WK_Mailer {

	const OPT_RECIPIENT      = 'wk_cf_recipient';
	const OPT_SUBJECT        = 'wk_cf_subject';
	const OPT_MAIL_METHOD    = 'wk_cf_mail_method';
	const OPT_MAIL_PRESET    = 'wk_cf_mail_preset';
	const OPT_SMTP_ENABLED   = 'wk_cf_smtp_enabled';
	const OPT_SMTP_HOST      = 'wk_cf_smtp_host';
	const OPT_SMTP_PORT      = 'wk_cf_smtp_port';
	const OPT_SMTP_ENCRYPT   = 'wk_cf_smtp_encryption';
	const OPT_SMTP_USER      = 'wk_cf_smtp_user';
	const OPT_SMTP_PASS      = 'wk_cf_smtp_pass';
	const OPT_SMTP_FROM_MAIL = 'wk_cf_smtp_from_email';
	const OPT_SMTP_FROM_NAME = 'wk_cf_smtp_from_name';

	/**
	 * Register the SMTP transport hook.
	 */
	public function init() {
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
	}

	/**
	 * Whether the custom SMTP transport is enabled.
	 *
	 * @return bool
	 */
	public static function smtp_enabled() {
		return (bool) get_option( self::OPT_SMTP_ENABLED, false );
	}

	/**
	 * Resolve the recipient address (falls back to the site admin email).
	 *
	 * @return string
	 */
	public static function get_recipient() {
		$recipient = trim( (string) get_option( self::OPT_RECIPIENT, '' ) );
		if ( '' === $recipient || ! is_email( $recipient ) ) {
			$recipient = (string) get_option( 'admin_email' );
		}
		return $recipient;
	}

	/**
	 * Resolve the subject line, replacing the {site_name} placeholder.
	 *
	 * @param string $fallback Default subject when the option is empty.
	 * @return string
	 */
	public static function get_subject( $fallback = '' ) {
		$subject = trim( (string) get_option( self::OPT_SUBJECT, '' ) );
		if ( '' === $subject ) {
			$subject = '' !== $fallback ? $fallback : 'Contact request from {site_name}';
		}
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		return str_replace( '{site_name}', $site_name, $subject );
	}

	/**
	 * Apply the stored SMTP credentials to the PHPMailer instance.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
	 */
	public function configure_smtp( $phpmailer ) {
		if ( ! self::smtp_enabled() ) {
			return;
		}
		$host = trim( (string) get_option( self::OPT_SMTP_HOST, '' ) );
		if ( '' === $host ) {
			return;
		}
		$port       = (int) get_option( self::OPT_SMTP_PORT, 587 );
		$encryption = (string) get_option( self::OPT_SMTP_ENCRYPT, 'tls' );
		$user       = (string) get_option( self::OPT_SMTP_USER, '' );
		$pass       = (string) get_option( self::OPT_SMTP_PASS, '' );

		$phpmailer->isSMTP();
		$phpmailer->Host = $host;
		$phpmailer->Port = $port > 0 ? $port : 587;

		if ( '' !== $user ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $user;
			$phpmailer->Password = $pass;
		} else {
			$phpmailer->SMTPAuth = false;
		}

		if ( 'ssl' === $encryption ) {
			$phpmailer->SMTPSecure = 'ssl';
		} elseif ( 'tls' === $encryption ) {
			$phpmailer->SMTPSecure = 'tls';
		} else {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		}

		$from_email = trim( (string) get_option( self::OPT_SMTP_FROM_MAIL, '' ) );
		$from_name  = trim( (string) get_option( self::OPT_SMTP_FROM_NAME, '' ) );
		if ( '' !== $from_email && is_email( $from_email ) ) {
			$phpmailer->setFrom(
				$from_email,
				'' !== $from_name ? $from_name : $phpmailer->FromName,
				false
			);
		}
	}

	/**
	 * Send a mail through wp_mail (SMTP applied transparently via the hook).
	 *
	 * @param string $to         Recipient.
	 * @param string $subject    Subject line.
	 * @param string $body       Plain text body.
	 * @param string $reply_to   Optional reply-to address.
	 * @param string $reply_name Optional reply-to display name.
	 * @return bool
	 */
	public static function send( $to, $subject, $body, $reply_to = '', $reply_name = '' ) {
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( '' !== $reply_to && is_email( $reply_to ) ) {
			$name      = '' !== $reply_name ? $reply_name : $reply_to;
			$headers[] = sprintf( 'Reply-To: %s <%s>', $name, $reply_to );
		}
		return (bool) wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Resolve the selected sending method (none|brevo|mailbox|manual).
	 *
	 * @return string
	 */
	public static function get_method() {
		$method = (string) get_option( self::OPT_MAIL_METHOD, '' );
		if ( '' === $method ) {
			$host = trim( (string) get_option( self::OPT_SMTP_HOST, '' ) );
			if ( self::smtp_enabled() && '' !== $host ) {
				return 'manual';
			}
			return 'none';
		}
		return in_array( $method, array( 'none', 'brevo', 'mailbox', 'manual' ), true ) ? $method : 'none';
	}

	/**
	 * Map a raw PHPMailer error string to a friendly message.
	 *
	 * @param string $raw Raw error string.
	 * @return string
	 */
	public static function friendly_error( $raw ) {
		$raw = strtolower( (string) $raw );
		if ( '' !== $raw ) {
			if ( strpos( $raw, 'authenticate' ) !== false || strpos( $raw, '535' ) !== false ) {
				return 'Login failed. Please check username and password / SMTP key.';
			}
			if ( strpos( $raw, 'certificate' ) !== false || strpos( $raw, 'ssl' ) !== false ) {
				return 'Encrypted connection failed. Please check encryption type (TLS/SSL) and port.';
			}
			if ( strpos( $raw, 'connect' ) !== false || strpos( $raw, 'timeout' ) !== false || strpos( $raw, 'refused' ) !== false ) {
				return 'Could not connect to the mail server. Please check host and port.';
			}
		}
		return 'The email could not be sent. Please check the settings or contact your provider.';
	}
}
