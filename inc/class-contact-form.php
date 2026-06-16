<?php
/**
 * WK_Contact_Form – Contact form block.
 *
 * Dynamic block (render_callback) with a REST submit endpoint. UI strings are
 * bilingual: English by default, German when the site/page locale starts with "de".
 * Spam protection via honeypot + per-IP rate limit.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WK_Contact_Form {

	const BLOCK_NAME  = 'widerruf-kontakt/contact-form';
	const REST_NS     = 'widerruf-kontakt/v1';
	const RATE_MAX    = 3;
	const RATE_WINDOW = 600;
	const MAX_NAME    = 120;
	const MAX_PHONE   = 40;
	const MAX_MESSAGE = 5000;

	/** @var bool */
	private $assets_enqueued = false;

	/** @var bool */
	private $config_printed = false;

	public function init() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		( new WK_Mailer() )->init();
	}

	public function register_block() {
		register_block_type( self::BLOCK_NAME, array(
			'render_callback' => array( $this, 'render_block' ),
		) );
	}

	public function register_routes() {
		register_rest_route( self::REST_NS, '/contact-form/submit', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_submit' ),
			'permission_callback' => '__return_true',
		) );
	}

	// -------------------------------------------------------------------------
	// Localisation
	// -------------------------------------------------------------------------

	public static function resolve_lang( $hint = '' ) {
		$hint = strtolower( (string) $hint );
		if ( 0 === strpos( $hint, 'de' ) ) {
			return 'de';
		}
		$locale = strtolower( (string) get_locale() );
		return ( 0 === strpos( $locale, 'de' ) ) ? 'de' : 'en';
	}

	public static function strings( $lang ) {
		$de = array(
			'name'          => 'Name',
			'email'         => 'E-Mail-Adresse',
			'phone'         => 'Telefonnummer',
			'message'       => 'Nachricht',
			'submit'        => 'Absenden',
			'sending'       => 'Wird gesendet…',
			'success'       => 'Vielen Dank! Ihre Nachricht wurde gesendet.',
			'consent'       => 'Ich stimme der Verarbeitung meiner Daten zur Bearbeitung meiner Anfrage zu. Ich kann meine Einwilligung jederzeit per E-Mail widerrufen. Es gelten die Datenschutzbestimmungen.',
			'required'      => 'Pflichtfeld',
			'err_required'  => 'Bitte füllen Sie dieses Feld aus.',
			'err_email'     => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
			'err_consent'   => 'Bitte stimmen Sie der Verarbeitung zu.',
			'err_generic'   => 'Die Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.',
			'err_rate'      => 'Zu viele Anfragen. Bitte versuchen Sie es in einigen Minuten erneut.',
			'honeypot'      => 'Postleitzahl',
			'label_name'    => 'Name',
			'label_email'   => 'E-Mail',
			'label_phone'   => 'Telefon',
			'label_message' => 'Nachricht',
		);
		$en = array(
			'name'          => 'Name',
			'email'         => 'Email address',
			'phone'         => 'Phone number',
			'message'       => 'Message',
			'submit'        => 'Send',
			'sending'       => 'Sending…',
			'success'       => 'Thank you! Your message has been sent.',
			'consent'       => 'I agree to the processing of my data to handle my request. I may withdraw my consent at any time by email. The privacy policy applies.',
			'required'      => 'Required',
			'err_required'  => 'Please fill out this field.',
			'err_email'     => 'Please enter a valid email address.',
			'err_consent'   => 'Please agree to the processing of your data.',
			'err_generic'   => 'The message could not be sent. Please try again later.',
			'err_rate'      => 'Too many requests. Please try again in a few minutes.',
			'honeypot'      => 'Postal code',
			'label_name'    => 'Name',
			'label_email'   => 'Email',
			'label_phone'   => 'Phone',
			'label_message' => 'Message',
		);
		return 'de' === $lang ? $de : $en;
	}

	public static function editor_strings() {
		$lang = self::resolve_lang();
		$s    = self::strings( $lang );
		$de   = ( 'de' === $lang );
		return array(
			'lang'             => $lang,
			'blockTitle'       => $de ? 'Kontaktformular' : 'Contact Form',
			'blockDescription' => $de
				? 'Schlankes, sicheres Kontaktformular mit konfigurierbaren Feldern, Spam-Schutz und E-Mail-Versand.'
				: 'Lean, secure contact form with configurable fields, spam protection and email delivery.',
			'panelFields'      => $de ? 'Felder' : 'Fields',
			'showName'         => $de ? 'Name anzeigen' : 'Show name',
			'nameRequired'     => $de ? 'Name als Pflichtfeld' : 'Name required',
			'showPhone'        => $de ? 'Telefon anzeigen' : 'Show phone',
			'phoneRequired'    => $de ? 'Telefon als Pflichtfeld' : 'Phone required',
			'fieldsNote'       => $de
				? 'E-Mail und Nachricht sind immer aktiv und Pflichtfelder.'
				: 'Email and message are always shown and required.',
			'panelConsent'     => $de ? 'Einwilligung' : 'Consent',
			'showConsent'      => $de ? 'Checkbox anzeigen' : 'Show checkbox',
			'consentEditHint'  => $de
				? 'Den Checkbox-Text direkt im Block bearbeiten. Links über die Editor-Werkzeugleiste setzen.'
				: 'Edit the checkbox text directly in the block. Add links via the editor toolbar.',
			'panelTexts'       => $de ? 'Texte' : 'Texts',
			'submitLabel'      => $de ? 'Button-Text' : 'Button text',
			'submitHelp'       => $de ? 'Leer = automatisch je nach Sprache.' : 'Empty = automatic based on language.',
			'successLabel'     => $de ? 'Erfolgsmeldung' : 'Success message',
			'successHelp'      => $de
				? 'Ersetzt das Formular nach dem Absenden. Leer = Standard.'
				: 'Replaces the form after submission. Empty = default.',
			'name'             => $s['name'],
			'email'            => $s['email'],
			'phone'            => $s['phone'],
			'message'          => $s['message'],
			'consent'          => $s['consent'],
			'submit'           => $s['submit'],
			'success'          => $s['success'],
		);
	}

	public function enqueue_editor_assets() {
		$js_path  = WK_PATH . 'assets/js/contact-form-editor.js';
		$css_path = WK_PATH . 'assets/css/contact-form.css';
		wp_enqueue_script(
			'wk-contact-form-editor',
			WK_URL . 'assets/js/contact-form-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : WK_VERSION,
			true
		);
		wp_localize_script( 'wk-contact-form-editor', 'wkContactFormEditor', self::editor_strings() );
		wp_enqueue_style(
			'wk-contact-form',
			WK_URL . 'assets/css/contact-form.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : WK_VERSION
		);
	}

	// -------------------------------------------------------------------------
	// Frontend rendering
	// -------------------------------------------------------------------------

	public function render_block( $attributes ) {
		$lang           = self::resolve_lang();
		$s              = self::strings( $lang );
		$show_name      = ! isset( $attributes['showName'] ) || (bool) $attributes['showName'];
		$name_required  = ! empty( $attributes['nameRequired'] );
		$show_phone     = ! isset( $attributes['showPhone'] ) || (bool) $attributes['showPhone'];
		$phone_required = ! empty( $attributes['phoneRequired'] );
		$show_consent   = ! isset( $attributes['showConsent'] ) || (bool) $attributes['showConsent'];

		$consent_html = isset( $attributes['consentHtml'] ) && '' !== $attributes['consentHtml']
			? wp_kses_post( $attributes['consentHtml'] )
			: esc_html( $s['consent'] );

		$submit_label = isset( $attributes['submitLabel'] ) && '' !== trim( (string) $attributes['submitLabel'] )
			? esc_html( $attributes['submitLabel'] )
			: esc_html( $s['submit'] );

		$success_message = isset( $attributes['successMessage'] ) && '' !== trim( (string) $attributes['successMessage'] )
			? $attributes['successMessage']
			: $s['success'];

		$form_id = isset( $attributes['formId'] ) && '' !== $attributes['formId']
			? sanitize_html_class( $attributes['formId'] )
			: 'wkcf-' . wp_generate_password( 8, false, false );

		$this->enqueue_frontend_assets( $lang, $s );

		$active_top = $show_name ? array( 'name', 'email' ) : array( 'email' );
		if ( $show_phone ) {
			$active_top[] = 'phone';
		}
		$cols     = count( $active_top );
		$req_mark = '<span class="wk-cf-req" aria-hidden="true">*</span>';
		$fid      = esc_attr( $form_id );

		ob_start();
		?>
		<div class="wk-contact-form-wrap"
			data-wk-contact-form
			data-form-id="<?php echo $fid; ?>"
			data-success="<?php echo esc_attr( $success_message ); ?>">
			<form class="wk-contact-form" novalidate>
				<div class="wk-cf-feedback" role="alert" aria-live="assertive" hidden></div>
				<div class="wk-cf-row wk-cf-row--fields" data-cols="<?php echo esc_attr( $cols ); ?>">
					<?php if ( $show_name ) : ?>
						<p class="wk-cf-field">
							<label for="<?php echo $fid; ?>-name"><?php echo esc_html( $s['name'] ); ?><?php echo $name_required ? ' ' . $req_mark : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
							<input type="text" id="<?php echo $fid; ?>-name" name="name" maxlength="<?php echo esc_attr( self::MAX_NAME ); ?>" autocomplete="name" <?php echo $name_required ? 'required aria-required="true"' : ''; ?> />
						</p>
					<?php endif; ?>
					<p class="wk-cf-field">
						<label for="<?php echo $fid; ?>-email"><?php echo esc_html( $s['email'] ); ?> <?php echo $req_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<input type="email" id="<?php echo $fid; ?>-email" name="email" autocomplete="email" required aria-required="true" />
					</p>
					<?php if ( $show_phone ) : ?>
						<p class="wk-cf-field">
							<label for="<?php echo $fid; ?>-phone"><?php echo esc_html( $s['phone'] ); ?><?php echo $phone_required ? ' ' . $req_mark : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
							<input type="tel" id="<?php echo $fid; ?>-phone" name="phone" maxlength="<?php echo esc_attr( self::MAX_PHONE ); ?>" autocomplete="tel" <?php echo $phone_required ? 'required aria-required="true"' : ''; ?> />
						</p>
					<?php endif; ?>
				</div>
				<div class="wk-cf-row">
					<p class="wk-cf-field">
						<label for="<?php echo $fid; ?>-message"><?php echo esc_html( $s['message'] ); ?> <?php echo $req_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
						<textarea id="<?php echo $fid; ?>-message" name="message" rows="6" maxlength="<?php echo esc_attr( self::MAX_MESSAGE ); ?>" required aria-required="true"></textarea>
					</p>
				</div>
				<div class="wk-cf-hp" aria-hidden="true">
					<label for="<?php echo $fid; ?>-postcode"><?php echo esc_html( $s['honeypot'] ); ?></label>
					<input type="text" id="<?php echo $fid; ?>-postcode" name="postcode" tabindex="-1" autocomplete="off" />
				</div>
				<?php if ( $show_consent ) : ?>
					<div class="wk-cf-row wk-cf-consent">
						<label class="wk-cf-consent-label">
							<input type="checkbox" name="consent" value="1" required aria-required="true" />
							<span class="wk-cf-consent-text"><?php echo $consent_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</label>
					</div>
				<?php endif; ?>
				<div class="wk-cf-row wk-cf-submit-row wp-block-buttons">
					<div class="wp-block-button">
						<button type="submit" class="wk-cf-submit wp-block-button__link wp-element-button">
							<span class="wk-cf-submit-label"><?php echo $submit_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</button>
					</div>
				</div>
				<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	private function enqueue_frontend_assets( $lang, $s ) {
		if ( ! $this->assets_enqueued ) {
			$css_path = WK_PATH . 'assets/css/contact-form.css';
			$js_path  = WK_PATH . 'assets/js/contact-form.js';
			wp_enqueue_style( 'wk-contact-form', WK_URL . 'assets/css/contact-form.css', array(), file_exists( $css_path ) ? filemtime( $css_path ) : WK_VERSION );
			wp_enqueue_script( 'wk-contact-form', WK_URL . 'assets/js/contact-form.js', array(), file_exists( $js_path ) ? filemtime( $js_path ) : WK_VERSION, true );
			$this->assets_enqueued = true;
		}
		if ( ! $this->config_printed ) {
			wp_localize_script( 'wk-contact-form', 'wkContactForm', array(
				'restUrl' => esc_url_raw( rest_url( self::REST_NS . '/contact-form/submit' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'lang'    => $lang,
				'strings' => array(
					'sending'     => $s['sending'],
					'errRequired' => $s['err_required'],
					'errEmail'    => $s['err_email'],
					'errConsent'  => $s['err_consent'],
					'errGeneric'  => $s['err_generic'],
				),
			) );
			$this->config_printed = true;
		}
	}

	// -------------------------------------------------------------------------
	// REST submit
	// -------------------------------------------------------------------------

	public function handle_submit( $request ) {
		$lang = self::resolve_lang( (string) $request->get_param( 'lang' ) );
		$s    = self::strings( $lang );

		$nonce = $request->get_header( 'X-WP-Nonce' ) ?: (string) $request->get_param( '_wpnonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( array( 'message' => $s['err_generic'] ), 403 );
		}
		if ( '' !== trim( (string) $request->get_param( 'postcode' ) ) ) {
			return new WP_REST_Response( array( 'message' => $s['success'] ), 200 );
		}
		if ( $this->is_rate_limited() ) {
			return new WP_REST_Response( array( 'message' => $s['err_rate'] ), 429 );
		}

		$name    = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$phone   = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$message = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
		$consent = ! empty( $request->get_param( 'consent' ) );

		$errors = array();
		if ( '' === $email || ! is_email( $email ) ) {
			$errors['email'] = $s['err_email'];
		}
		if ( '' === $message ) {
			$errors['message'] = $s['err_required'];
		}
		if ( null !== $request->get_param( 'consent' ) && ! $consent ) {
			$errors['consent'] = $s['err_consent'];
		}
		if ( ! empty( $errors ) ) {
			return new WP_REST_Response( array( 'message' => $s['err_generic'], 'errors' => $errors ), 422 );
		}

		$name    = mb_substr( $name, 0, self::MAX_NAME );
		$phone   = mb_substr( $phone, 0, self::MAX_PHONE );
		$message = mb_substr( $message, 0, self::MAX_MESSAGE );

		$recipient  = WK_Mailer::get_recipient();
		$subject    = WK_Mailer::get_subject();
		$body       = $this->build_body( $name, $email, $phone, $message, $s );
		$reply_name = '' !== $name ? $name : $email;

		$sent = WK_Mailer::send( $recipient, $subject, $body, $email, $reply_name );
		if ( ! $sent ) {
			return new WP_REST_Response( array( 'message' => $s['err_generic'] ), 500 );
		}

		$this->bump_rate_limit();
		return new WP_REST_Response( array( 'message' => $s['success'] ), 200 );
	}

	private function build_body( $name, $email, $phone, $message, $s ) {
		$lines = array();
		if ( '' !== $name )  { $lines[] = $s['label_name'] . ': ' . $name; }
		$lines[] = $s['label_email'] . ': ' . $email;
		if ( '' !== $phone ) { $lines[] = $s['label_phone'] . ': ' . $phone; }
		$lines[] = '';
		$lines[] = $s['label_message'] . ':';
		$lines[] = $message;
		return implode( "\n", $lines );
	}

	private function rate_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
		return 'wk_cf_' . md5( $ip );
	}

	private function is_rate_limited() {
		return (int) get_transient( $this->rate_key() ) >= self::RATE_MAX;
	}

	private function bump_rate_limit() {
		$key = $this->rate_key();
		set_transient( $key, (int) get_transient( $key ) + 1, self::RATE_WINDOW );
	}
}
