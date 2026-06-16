<?php
/**
 * WK_Mail_Settings – guided SMTP setup for the form blocks.
 *
 * Brevo (recommended), existing mailbox (provider preset) or advanced manual
 * SMTP. Mirrors the GutenBlock Pro wizard UX with wk_* option keys.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WK_Mail_Settings {

	const SETTINGS_GROUP = 'wk_mail_settings';
	const OPT_TEST_STATUS  = 'wk_cf_test_status';
	const OPT_TEST_MESSAGE = 'wk_cf_test_message';

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_wk_cf_test_mail', array( $this, 'ajax_test_mail' ) );
	}

	public function add_submenu() {
		$de    = wk_is_de();
		$label = $de ? 'E-Mail-Versand' : 'Email delivery';
		add_submenu_page(
			'widerruf-kontakt',
			$label,
			$label,
			'manage_options',
			'widerruf-kontakt-mail',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		$m = 'WK_Mailer';
		register_setting( self::SETTINGS_GROUP, $m::OPT_RECIPIENT, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ) );
		register_setting( self::SETTINGS_GROUP, $m::OPT_SUBJECT,   array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( self::SETTINGS_GROUP, $m::OPT_MAIL_PRESET, array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_preset' ), 'default' => 'ionos' ) );
		register_setting( self::SETTINGS_GROUP, $m::OPT_MAIL_METHOD, array( 'type' => 'string', 'sanitize_callback' => array( $this, 'sanitize_mail_method' ), 'default' => 'none' ) );
	}

	public function sanitize_preset( $value ) {
		$value   = is_string( $value ) ? $value : '';
		$servers = WK_Mail_Presets::servers();
		return isset( $servers[ $value ] ) ? $value : 'ionos';
	}

	public function sanitize_mail_method( $value ) {
		$m      = 'WK_Mailer';
		$method = is_string( $value ) ? $value : 'none';
		if ( ! in_array( $method, array( 'none', 'brevo', 'mailbox', 'manual' ), true ) ) {
			$method = 'none';
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- options.php verifies the settings-group nonce before sanitization runs.
		$post = wp_unslash( $_POST );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$keep_pass = (string) get_option( $m::OPT_SMTP_PASS, '' );

		if ( 'brevo' === $method ) {
			$server = WK_Mail_Presets::get_server( 'brevo' );
			$pass   = isset( $post['wk_brevo_pass'] ) ? trim( (string) $post['wk_brevo_pass'] ) : '';
			update_option( $m::OPT_SMTP_ENABLED, true );
			update_option( $m::OPT_SMTP_HOST, $server['host'] );
			update_option( $m::OPT_SMTP_PORT, $server['port'] );
			update_option( $m::OPT_SMTP_ENCRYPT, $server['encryption'] );
			update_option( $m::OPT_SMTP_USER, isset( $post['wk_brevo_user'] ) ? sanitize_text_field( $post['wk_brevo_user'] ) : '' );
			update_option( $m::OPT_SMTP_PASS, '' !== $pass ? $pass : $keep_pass );
			update_option( $m::OPT_SMTP_FROM_MAIL, isset( $post['wk_brevo_from_email'] ) ? sanitize_email( $post['wk_brevo_from_email'] ) : '' );
			update_option( $m::OPT_SMTP_FROM_NAME, isset( $post['wk_brevo_from_name'] ) ? sanitize_text_field( $post['wk_brevo_from_name'] ) : '' );
		} elseif ( 'mailbox' === $method ) {
			$preset_slug = isset( $post[ $m::OPT_MAIL_PRESET ] ) ? $this->sanitize_preset( $post[ $m::OPT_MAIL_PRESET ] ) : 'ionos';
			$server      = WK_Mail_Presets::get_server( $preset_slug );
			$email       = isset( $post['wk_mailbox_email'] ) ? sanitize_email( $post['wk_mailbox_email'] ) : '';
			$pass        = isset( $post['wk_mailbox_pass'] ) ? trim( (string) $post['wk_mailbox_pass'] ) : '';
			update_option( $m::OPT_SMTP_ENABLED, true );
			update_option( $m::OPT_SMTP_HOST, $server ? $server['host'] : '' );
			update_option( $m::OPT_SMTP_PORT, $server ? $server['port'] : 587 );
			update_option( $m::OPT_SMTP_ENCRYPT, $server ? $server['encryption'] : 'tls' );
			update_option( $m::OPT_SMTP_USER, $email );
			update_option( $m::OPT_SMTP_PASS, '' !== $pass ? $pass : $keep_pass );
			update_option( $m::OPT_SMTP_FROM_MAIL, $email );
			update_option( $m::OPT_SMTP_FROM_NAME, isset( $post['wk_mailbox_from_name'] ) ? sanitize_text_field( $post['wk_mailbox_from_name'] ) : '' );
		} elseif ( 'manual' === $method ) {
			$pass = isset( $post['wk_manual_pass'] ) ? trim( (string) $post['wk_manual_pass'] ) : '';
			update_option( $m::OPT_SMTP_ENABLED, true );
			update_option( $m::OPT_SMTP_HOST, isset( $post['wk_manual_host'] ) ? sanitize_text_field( $post['wk_manual_host'] ) : '' );
			update_option( $m::OPT_SMTP_PORT, isset( $post['wk_manual_port'] ) ? absint( $post['wk_manual_port'] ) : 587 );
			update_option( $m::OPT_SMTP_ENCRYPT, $this->normalise_enc( isset( $post['wk_manual_encryption'] ) ? $post['wk_manual_encryption'] : 'tls' ) );
			update_option( $m::OPT_SMTP_USER, isset( $post['wk_manual_user'] ) ? sanitize_text_field( $post['wk_manual_user'] ) : '' );
			update_option( $m::OPT_SMTP_PASS, '' !== $pass ? $pass : $keep_pass );
			update_option( $m::OPT_SMTP_FROM_MAIL, isset( $post['wk_manual_from_email'] ) ? sanitize_email( $post['wk_manual_from_email'] ) : '' );
			update_option( $m::OPT_SMTP_FROM_NAME, isset( $post['wk_manual_from_name'] ) ? sanitize_text_field( $post['wk_manual_from_name'] ) : '' );
		} else {
			update_option( $m::OPT_SMTP_ENABLED, false );
		}
		return $method;
	}

	private function normalise_enc( $value ) {
		$value = is_string( $value ) ? $value : 'tls';
		return in_array( $value, array( 'tls', 'ssl', 'none' ), true ) ? $value : 'tls';
	}

	public function ajax_test_mail() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
		}
		check_ajax_referer( 'wk_cf_test', 'nonce' );

		$captured = '';
		$capture  = function ( $wp_error ) use ( &$captured ) {
			if ( is_wp_error( $wp_error ) ) {
				$captured = $wp_error->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $capture );

		$de        = wk_is_de();
		$recipient = WK_Mailer::get_recipient();
		$subject   = sprintf( '[Test] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$body      = $de
			? 'Dies ist eine Test-E-Mail von Widerruf & Kontakt. Wenn Sie diese erhalten haben, funktioniert der Versand.'
			: 'This is a test email from Widerruf & Kontakt. If you received it, sending works.';

		$sent = WK_Mailer::send( $recipient, $subject, $body );
		remove_action( 'wp_mail_failed', $capture );

		if ( $sent ) {
			$msg = $de
				? sprintf( 'Test-E-Mail an %s gesendet.', $recipient )
				: sprintf( 'Test email sent to %s.', $recipient );
			update_option( self::OPT_TEST_STATUS, 'ok' );
			update_option( self::OPT_TEST_MESSAGE, $msg );
			wp_send_json_success( array( 'message' => $msg ) );
		}

		$msg = WK_Mailer::friendly_error( $captured );
		update_option( self::OPT_TEST_STATUS, 'fail' );
		update_option( self::OPT_TEST_MESSAGE, $msg );
		wp_send_json_error( array( 'message' => $msg ) );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$de         = wk_is_de();
		$m          = 'WK_Mailer';
		$recipient  = get_option( $m::OPT_RECIPIENT, get_option( 'admin_email' ) );
		$subject    = get_option( $m::OPT_SUBJECT, '' );
		$method     = WK_Mailer::get_method();
		$preset     = get_option( $m::OPT_MAIL_PRESET, 'ionos' );
		$host       = get_option( $m::OPT_SMTP_HOST, '' );
		$port       = (int) get_option( $m::OPT_SMTP_PORT, 587 );
		$encryption = get_option( $m::OPT_SMTP_ENCRYPT, 'tls' );
		$user       = get_option( $m::OPT_SMTP_USER, '' );
		$has_pass   = '' !== (string) get_option( $m::OPT_SMTP_PASS, '' );
		$from_email = get_option( $m::OPT_SMTP_FROM_MAIL, '' );
		$from_name  = get_option( $m::OPT_SMTP_FROM_NAME, '' );
		$pass_ph    = $has_pass ? ( $de ? '•••••••• (gespeichert – leer lassen zum Behalten)' : '•••••••• (saved – leave empty to keep)' ) : '';
		$providers  = WK_Mail_Presets::mailbox_providers();
		$brevo_sum  = WK_Mail_Presets::summary( 'brevo' );
		$t_status   = get_option( self::OPT_TEST_STATUS, '' );
		$t_message  = get_option( self::OPT_TEST_MESSAGE, '' );
		$display    = ( 'none' === $method ) ? 'brevo' : $method;
		$brevo_url  = $de
			? 'https://help.brevo.com/hc/de/articles/209467485'
			: 'https://help.brevo.com/hc/en-us/articles/209467485';
		$this->print_inline_assets( $de );
		?>
		<div class="wrap wk-mail-settings" data-method="<?php echo esc_attr( $display ); ?>">
			<h1><?php echo esc_html( $de ? 'E-Mail-Versand' : 'Email delivery' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<h2><?php echo esc_html( $de ? 'Empfänger' : 'Recipient' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="wk_cf_recipient"><?php echo esc_html( $de ? 'Empfänger-E-Mail' : 'Recipient email' ); ?></label></th>
						<td>
							<input type="email" class="regular-text" id="wk_cf_recipient" name="<?php echo esc_attr( $m::OPT_RECIPIENT ); ?>" value="<?php echo esc_attr( $recipient ); ?>" />
							<p class="description"><?php echo esc_html( $de ? 'Eingehende Anfragen werden an diese Adresse gesendet.' : 'Incoming submissions are sent to this address.' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="wk_cf_subject"><?php echo esc_html( $de ? 'Betreff' : 'Subject' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="wk_cf_subject" name="<?php echo esc_attr( $m::OPT_SUBJECT ); ?>" value="<?php echo esc_attr( $subject ); ?>" placeholder="Contact request from {site_name}" />
							<p class="description"><?php echo esc_html( $de ? 'Platzhalter {site_name} wird durch den Website-Namen ersetzt.' : 'Placeholder {site_name} is replaced with the site name.' ); ?></p>
						</td>
					</tr>
				</table>
				<h2><?php echo esc_html( $de ? 'Versandweg einrichten' : 'Configure sending' ); ?></h2>
				<p class="description" style="max-width:640px;margin-bottom:1rem;"><?php echo esc_html( $de ? 'Damit Formulare zuverlässig ankommen, verbinde ein echtes E-Mail-Postfach oder einen Versanddienst wie Brevo.' : 'For reliable delivery, connect a real email mailbox or a sending service like Brevo.' ); ?></p>
				<div class="wk-cf-methods">
					<?php
					$this->card( 'brevo',   $display, $de ? 'Empfohlen: Brevo'               : 'Recommended: Brevo',               $de ? 'Kostenloser Versanddienst – am einfachsten.'          : 'Free sending service – easiest to set up.' );
					$this->card( 'mailbox', $display, $de ? 'Vorhandenes E-Mail-Postfach'     : 'Existing mailbox',                 $de ? 'Nutze dein bestehendes Postfach (IONOS, Strato …).'   : 'Use your existing mailbox (IONOS, Strato …).' );
					$this->card( 'manual',  $display, $de ? 'Erweitert: Manuell'              : 'Advanced: Manual',                 $de ? 'SMTP-Daten selbst eintragen.'                         : 'Enter SMTP credentials manually.' );
					?>
				</div>

				<div class="wk-cf-panel" data-panel="brevo">
					<ol class="wk-cf-steps">
						<li><?php echo esc_html( $de ? 'Kostenloses Konto bei Brevo erstellen und Absender bestätigen.' : 'Create a free Brevo account and confirm your sender address.' ); ?></li>
						<li><?php printf( $de ? 'Unter "SMTP &amp; API" einen %s erzeugen.' : 'Under "SMTP &amp; API", create a %s.', '<a href="' . esc_url( $brevo_url ) . '" target="_blank" rel="noopener noreferrer">' . ( $de ? 'SMTP-Schlüssel' : 'SMTP key' ) . '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
						<li><?php echo esc_html( $de ? 'SMTP-Login und SMTP-Schlüssel hier einfügen.' : 'Paste the SMTP login and SMTP key below.' ); ?></li>
					</ol>
					<table class="form-table" role="presentation">
						<tr><th><label for="wk_brevo_user"><?php echo esc_html( $de ? 'SMTP-Login (Benutzername)' : 'SMTP login (username)' ); ?></label></th><td><input type="text" class="regular-text" id="wk_brevo_user" name="wk_brevo_user" value="<?php echo 'brevo' === $method ? esc_attr( $user ) : ''; ?>" autocomplete="off" /></td></tr>
						<tr><th><label for="wk_brevo_pass"><?php echo esc_html( $de ? 'SMTP-Schlüssel' : 'SMTP key' ); ?></label></th><td><input type="password" class="regular-text" id="wk_brevo_pass" name="wk_brevo_pass" value="" autocomplete="new-password" placeholder="<?php echo 'brevo' === $method ? esc_attr( $pass_ph ) : ''; ?>" /></td></tr>
						<tr><th><label for="wk_brevo_from_email"><?php echo esc_html( $de ? 'Absender-E-Mail' : 'From email' ); ?></label></th><td><input type="email" class="regular-text" id="wk_brevo_from_email" name="wk_brevo_from_email" value="<?php echo 'brevo' === $method ? esc_attr( $from_email ) : ''; ?>" /></td></tr>
						<tr><th><label for="wk_brevo_from_name"><?php echo esc_html( $de ? 'Absender-Name' : 'From name' ); ?></label></th><td><input type="text" class="regular-text" id="wk_brevo_from_name" name="wk_brevo_from_name" value="<?php echo 'brevo' === $method ? esc_attr( $from_name ) : ''; ?>" /></td></tr>
					</table>
					<p class="description"><?php printf( $de ? 'Automatisch gesetzt: %s' : 'Auto-set: %s', '<code>' . esc_html( $brevo_sum ) . '</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>

				<div class="wk-cf-panel" data-panel="mailbox">
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="wk_cf_preset"><?php echo esc_html( $de ? 'Anbieter' : 'Provider' ); ?></label></th>
							<td>
								<select id="wk_cf_preset" name="<?php echo esc_attr( $m::OPT_MAIL_PRESET ); ?>">
									<?php foreach ( $providers as $slug => $prov ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>" data-hint="<?php echo esc_attr( $prov['hint'] ); ?>" data-summary="<?php echo esc_attr( WK_Mail_Presets::summary( $slug ) ); ?>" <?php selected( $preset, $slug ); ?>><?php echo esc_html( $prov['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description wk-cf-preset-hint"></p>
							</td>
						</tr>
						<tr><th><label for="wk_mailbox_email"><?php echo esc_html( $de ? 'E-Mail-Adresse' : 'Email address' ); ?></label></th><td><input type="email" class="regular-text" id="wk_mailbox_email" name="wk_mailbox_email" value="<?php echo 'mailbox' === $method ? esc_attr( $from_email ) : ''; ?>" placeholder="info@example.com" /></td></tr>
						<tr><th><label for="wk_mailbox_pass"><?php echo esc_html( $de ? 'Passwort' : 'Password' ); ?></label></th><td><input type="password" class="regular-text" id="wk_mailbox_pass" name="wk_mailbox_pass" value="" autocomplete="new-password" placeholder="<?php echo 'mailbox' === $method ? esc_attr( $pass_ph ) : ''; ?>" /></td></tr>
						<tr><th><label for="wk_mailbox_from_name"><?php echo esc_html( $de ? 'Absender-Name' : 'From name' ); ?></label></th><td><input type="text" class="regular-text" id="wk_mailbox_from_name" name="wk_mailbox_from_name" value="<?php echo 'mailbox' === $method ? esc_attr( $from_name ) : ''; ?>" /></td></tr>
					</table>
					<p class="description wk-cf-mailbox-summary"></p>
				</div>

				<div class="wk-cf-panel" data-panel="manual">
					<table class="form-table" role="presentation">
						<tr><th><label for="wk_manual_host">SMTP Host</label></th><td><input type="text" class="regular-text" id="wk_manual_host" name="wk_manual_host" value="<?php echo 'manual' === $method ? esc_attr( $host ) : ''; ?>" placeholder="smtp.example.com" /></td></tr>
						<tr><th><label for="wk_manual_port">Port</label></th><td><input type="number" class="small-text" id="wk_manual_port" name="wk_manual_port" value="<?php echo 'manual' === $method ? esc_attr( $port ) : '587'; ?>" min="1" max="65535" /><p class="description">587 (TLS), 465 (SSL)</p></td></tr>
						<tr>
							<th><label for="wk_manual_enc"><?php echo esc_html( $de ? 'Verschlüsselung' : 'Encryption' ); ?></label></th>
							<td>
								<select id="wk_manual_enc" name="wk_manual_encryption">
									<option value="tls" <?php selected( 'manual' === $method ? $encryption : 'tls', 'tls' ); ?>>TLS</option>
									<option value="ssl" <?php selected( 'manual' === $method ? $encryption : 'tls', 'ssl' ); ?>>SSL</option>
									<option value="none" <?php selected( 'manual' === $method ? $encryption : 'tls', 'none' ); ?>><?php echo esc_html( $de ? 'Keine' : 'None' ); ?></option>
								</select>
							</td>
						</tr>
						<tr><th><label for="wk_manual_user"><?php echo esc_html( $de ? 'Benutzername' : 'Username' ); ?></label></th><td><input type="text" class="regular-text" id="wk_manual_user" name="wk_manual_user" value="<?php echo 'manual' === $method ? esc_attr( $user ) : ''; ?>" autocomplete="off" /></td></tr>
						<tr><th><label for="wk_manual_pass"><?php echo esc_html( $de ? 'Passwort' : 'Password' ); ?></label></th><td><input type="password" class="regular-text" id="wk_manual_pass" name="wk_manual_pass" value="" autocomplete="new-password" placeholder="<?php echo 'manual' === $method ? esc_attr( $pass_ph ) : ''; ?>" /></td></tr>
						<tr><th><label for="wk_manual_from_email"><?php echo esc_html( $de ? 'Absender-Adresse' : 'From address' ); ?></label></th><td><input type="email" class="regular-text" id="wk_manual_from_email" name="wk_manual_from_email" value="<?php echo 'manual' === $method ? esc_attr( $from_email ) : ''; ?>" /></td></tr>
						<tr><th><label for="wk_manual_from_name"><?php echo esc_html( $de ? 'Absender-Name' : 'From name' ); ?></label></th><td><input type="text" class="regular-text" id="wk_manual_from_name" name="wk_manual_from_name" value="<?php echo 'manual' === $method ? esc_attr( $from_name ) : ''; ?>" /></td></tr>
					</table>
				</div>
				<?php submit_button( $de ? 'Einstellungen speichern' : 'Save settings' ); ?>
			</form>

			<h2><?php echo esc_html( $de ? 'Test-E-Mail' : 'Test email' ); ?></h2>
			<p class="description"><?php echo esc_html( $de ? 'Sendet eine Test-E-Mail an den gespeicherten Empfänger. Bitte zuerst speichern.' : 'Sends a test email to the saved recipient. Save first.' ); ?></p>
			<p>
				<button type="button" class="button button-secondary" id="wk-cf-test-mail"><?php echo esc_html( $de ? 'Test-E-Mail senden' : 'Send test email' ); ?></button>
				<span id="wk-cf-test-result" class="wk-cf-test-result<?php echo $t_status ? ' is-' . esc_attr( $t_status ) : ''; ?>"><?php echo esc_html( $t_message ); ?></span>
			</p>
		</div>
		<?php
	}

	private function card( $key, $current, $title, $desc ) {
		$m = 'WK_Mailer';
		?>
		<label class="wk-cf-method-card<?php echo $current === $key ? ' is-selected' : ''; ?>">
			<input type="radio" name="<?php echo esc_attr( $m::OPT_MAIL_METHOD ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current, $key ); ?> />
			<span class="wk-cf-method-title"><?php echo esc_html( $title ); ?></span>
			<span class="wk-cf-method-desc"><?php echo esc_html( $desc ); ?></span>
		</label>
		<?php
	}

	private function print_inline_assets( $de ) {
		?>
		<style>
		.wk-cf-methods{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;max-width:900px;margin:1rem 0 1.5rem}
		.wk-cf-method-card{position:relative;display:flex;flex-direction:column;gap:.3rem;padding:1rem 1rem 1rem 2.4rem;background:#fff;border:1px solid #c3c4c7;border-radius:6px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
		.wk-cf-method-card:hover{border-color:#2271b1}.wk-cf-method-card.is-selected{border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
		.wk-cf-method-card input[type="radio"]{position:absolute;top:1.1rem;left:.9rem;margin:0}
		.wk-cf-method-title{font-weight:600;font-size:14px}.wk-cf-method-desc{color:#646970;font-size:12px;line-height:1.4}
		.wk-cf-panel{display:none;max-width:760px;margin:0 0 1rem;padding:.5rem 1.25rem 1rem;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.wk-mail-settings[data-method="brevo"] .wk-cf-panel[data-panel="brevo"],
		.wk-mail-settings[data-method="mailbox"] .wk-cf-panel[data-panel="mailbox"],
		.wk-mail-settings[data-method="manual"] .wk-cf-panel[data-panel="manual"]{display:block}
		.wk-cf-steps{max-width:640px;margin:1rem 0;padding-left:1.4rem;line-height:1.6}
		.wk-cf-test-result{margin-left:10px;font-weight:500}.wk-cf-test-result.is-ok{color:#008a20}.wk-cf-test-result.is-fail{color:#d63638}
		</style>
		<script>
		(function(){
			var config={ajaxUrl:<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,nonce:<?php echo wp_json_encode( wp_create_nonce( 'wk_cf_test' ) ); ?>,autoPrefix:<?php echo wp_json_encode( $de ? 'Automatisch gesetzt: ' : 'Auto-set: ' ); ?>,strings:{sending:<?php echo wp_json_encode( $de ? 'Sende…' : 'Sending…' ); ?>,error:<?php echo wp_json_encode( $de ? 'Fehler.' : 'Error.' ); ?>}};
			function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
			ready(function(){
				var wrap=document.querySelector('.wk-mail-settings');if(!wrap)return;
				function sel(method){wrap.dataset.method=method;wrap.querySelectorAll('.wk-cf-method-card').forEach(function(c){var r=c.querySelector('input[type="radio"]');c.classList.toggle('is-selected',!!r&&r.checked);});}
				wrap.querySelectorAll('.wk-cf-method-card input[type="radio"]').forEach(function(r){r.addEventListener('change',function(){if(r.checked)sel(r.value);});});
				var ps=document.getElementById('wk_cf_preset'),ht=wrap.querySelector('.wk-cf-preset-hint'),su=wrap.querySelector('.wk-cf-mailbox-summary');
				function updateHint(){if(!ps)return;var o=ps.options[ps.selectedIndex];if(!o)return;if(ht)ht.textContent=o.getAttribute('data-hint')||'';if(su){var s=o.getAttribute('data-summary')||'';su.textContent=s?config.autoPrefix+s:'';}if(o.value==='other'){var mr=wrap.querySelector('.wk-cf-method-card input[value="manual"]');if(mr){mr.checked=true;sel('manual');}}}
				if(ps){ps.addEventListener('change',updateHint);updateHint();}
				var btn=document.getElementById('wk-cf-test-mail'),out=document.getElementById('wk-cf-test-result');
				if(btn&&out){btn.addEventListener('click',function(){btn.disabled=true;out.className='wk-cf-test-result';out.textContent=config.strings.sending;var d=new FormData();d.append('action','wk_cf_test_mail');d.append('nonce',config.nonce);fetch(config.ajaxUrl,{method:'POST',credentials:'same-origin',body:d}).then(function(r){return r.json();}).then(function(res){var ok=res&&res.success;out.textContent=res&&res.data&&res.data.message?res.data.message:'';out.className='wk-cf-test-result '+(ok?'is-ok':'is-fail');}).catch(function(){out.textContent=config.strings.error;out.className='wk-cf-test-result is-fail';}).finally(function(){btn.disabled=false;});});}
			});
		})();
		</script>
		<?php
	}
}
