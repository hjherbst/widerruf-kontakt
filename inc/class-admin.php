<?php
/**
 * WK_Admin – Admin menu, guide page and "Create withdrawal page" helper.
 *
 * Top-level menu "Widerruf & Kontakt" with two subpages:
 *   1. Guide (default) – step-by-step + quick-start button + disclaimer.
 *   2. Email delivery  – registered by WK_Mail_Settings.
 *
 * @package WiderrufKontakt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WK_Admin {

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_wk_create_revocation_page', array( $this, 'handle_create_page' ) );
	}

	public function register_menu() {
		$de = wk_is_de();
		add_menu_page(
			$de ? 'Widerruf & Kontakt' : 'Widerruf & Kontakt',
			'Widerruf & Kontakt',
			'manage_options',
			'widerruf-kontakt',
			array( $this, 'render_guide' ),
			'dashicons-email-alt',
			81
		);
		add_submenu_page(
			'widerruf-kontakt',
			$de ? 'Anleitung' : 'Guide',
			$de ? 'Anleitung' : 'Guide',
			'manage_options',
			'widerruf-kontakt',
			array( $this, 'render_guide' )
		);
	}

	// -------------------------------------------------------------------------
	// "Create withdrawal page" handler
	// -------------------------------------------------------------------------

	public function handle_create_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wk_create_revocation_page' );

		$de      = wk_is_de();
		$title   = $de ? 'Widerruf' : 'Right of Withdrawal';
		$slug    = $de ? 'widerruf' : 'withdrawal';
		$content = $this->build_page_content( $de );

		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$redirect = admin_url( 'admin.php?page=widerruf-kontakt&wk_notice=page_exists&page_id=' . $existing->ID );
			wp_safe_redirect( esc_url_raw( $redirect ) );
			exit;
		}

		$page_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_type'    => 'page',
		) );

		if ( is_wp_error( $page_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=widerruf-kontakt&wk_notice=page_error' ) );
			exit;
		}

		wp_safe_redirect( get_edit_post_link( $page_id, 'raw' ) );
		exit;
	}

	private function build_page_content( $de ) {
		// Draft page: heading + merchant details placeholder + model text + block.
		if ( $de ) {
			$merchant_block = '<!-- wp:paragraph {"className":"wk-merchant-info"} -->' . "\n"
				. '<p class="wk-merchant-info"><strong>Händlerangaben (bitte ausfüllen):</strong><br>'
				. 'Firmenname: [Ihr Name / Firma]<br>'
				. 'Straße + Hausnummer: [Ihre Anschrift]<br>'
				. 'PLZ, Ort: [PLZ Ort]<br>'
				. 'E-Mail: [Ihre E-Mail-Adresse]</p>' . "\n"
				. '<!-- /wp:paragraph -->';

			$model_text_block = '<!-- wp:paragraph -->' . "\n"
				. '<p><strong>Muster-Widerrufsformular</strong> (bitte nur ausfüllen und absenden, wenn Sie den Vertrag widerrufen wollen)</p>' . "\n"
				. '<!-- /wp:paragraph -->' . "\n"
				. '<!-- wp:paragraph -->' . "\n"
				. '<p>An [Name des Unternehmers], [Anschrift des Unternehmers], [E-Mail des Unternehmers]:<br>'
				. 'Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*) / die Erbringung der folgenden Dienstleistung (*)<br>'
				. '– Bestellt am (*)/erhalten am (*)<br>'
				. '– Name des/der Verbraucher(s)<br>'
				. '– Anschrift des/der Verbraucher(s)<br>'
				. '– Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier)<br>'
				. '– Datum<br>'
				. '(*) Unzutreffendes streichen.</p>' . "\n"
				. '<!-- /wp:paragraph -->' . "\n"
				. '<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->';

			$form_note_block = '<!-- wp:paragraph {"fontSize":"small"} -->' . "\n"
				. '<p class="has-small-font-size">Alternativ können Sie das folgende Formular nutzen:</p>' . "\n"
				. '<!-- /wp:paragraph -->';
		} else {
			$merchant_block = '<!-- wp:paragraph {"className":"wk-merchant-info"} -->' . "\n"
				. '<p class="wk-merchant-info"><strong>Merchant details (please fill in):</strong><br>'
				. 'Company: [Your name / company]<br>'
				. 'Street: [Your address]<br>'
				. 'City: [Postcode City]<br>'
				. 'Email: [Your email address]</p>' . "\n"
				. '<!-- /wp:paragraph -->';

			$model_text_block = '<!-- wp:paragraph -->' . "\n"
				. '<p><strong>Model withdrawal form</strong> (complete and return this form only if you wish to withdraw from the contract)</p>' . "\n"
				. '<!-- /wp:paragraph -->' . "\n"
				. '<!-- wp:paragraph -->' . "\n"
				. '<p>To [Name of entrepreneur], [Address of entrepreneur], [Email of entrepreneur]:<br>'
				. 'I/We (*) hereby give notice that I/We (*) withdraw from my/our (*) contract of sale of the following goods (*) / for the provision of the following service (*)<br>'
				. '– Ordered on (*) / received on (*)<br>'
				. '– Name of consumer(s)<br>'
				. '– Address of consumer(s)<br>'
				. '– Signature of consumer(s) (only if this form is notified on paper)<br>'
				. '– Date<br>'
				. '(*) Delete as appropriate.</p>' . "\n"
				. '<!-- /wp:paragraph -->' . "\n"
				. '<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->';

			$form_note_block = '<!-- wp:paragraph {"fontSize":"small"} -->' . "\n"
				. '<p class="has-small-font-size">Alternatively, you can use the form below:</p>' . "\n"
				. '<!-- /wp:paragraph -->';
		}

		$form_block = '<!-- wp:widerruf-kontakt/revocation-form /-->';

		return $merchant_block . "\n\n"
			. $model_text_block . "\n\n"
			. $form_note_block . "\n\n"
			. $form_block;
	}

	// -------------------------------------------------------------------------
	// Guide page
	// -------------------------------------------------------------------------

	public function render_guide() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$de = wk_is_de();

		// Show status notices.
		$notice = isset( $_GET['wk_notice'] ) ? sanitize_key( $_GET['wk_notice'] ) : '';
		if ( 'page_exists' === $notice && ! empty( $_GET['page_id'] ) ) {
			$page_id   = (int) $_GET['page_id'];
			$edit_url  = get_edit_post_link( $page_id );
			$view_url  = get_permalink( $page_id );
			$msg = $de
				? sprintf( 'Es existiert bereits eine Seite mit diesem Namen. <a href="%s">Seite bearbeiten</a> | <a href="%s" target="_blank">Seite ansehen</a>', esc_url( $edit_url ), esc_url( $view_url ) )
				: sprintf( 'A page with this name already exists. <a href="%s">Edit page</a> | <a href="%s" target="_blank">View page</a>', esc_url( $edit_url ), esc_url( $view_url ) );
			echo '<div class="notice notice-warning"><p>' . wp_kses( $msg, array( 'a' => array( 'href' => array(), 'target' => array() ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( 'page_error' === $notice ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $de ? 'Die Seite konnte nicht angelegt werden.' : 'The page could not be created.' ) . '</p></div>';
		}
		?>
		<div class="wrap wk-guide">
			<h1>Widerruf & Kontakt</h1>
			<p class="wk-guide-version"><?php printf( $de ? 'Version %s' : 'Version %s', esc_html( WK_VERSION ) ); ?></p>

			<?php // Quick-start CTA. ?>
			<div class="wk-guide-cta">
				<h2><?php echo esc_html( $de ? '⚡ Schnellstart' : '⚡ Quick start' ); ?></h2>
				<p><?php echo esc_html( $de
					? 'Klicke auf den Button, um sofort eine Entwurfsseite mit dem Widerrufsformular und dem gesetzlich empfohlenen Mustertext anzulegen.'
					: 'Click the button to immediately create a draft page with the withdrawal form and the legally recommended model text.' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wk_create_revocation_page" />
					<?php wp_nonce_field( 'wk_create_revocation_page' ); ?>
					<button type="submit" class="button button-primary button-hero">
						<?php echo esc_html( $de ? '📄 Widerrufsseite erstellen' : '📄 Create withdrawal page' ); ?>
					</button>
				</form>
				<p class="description"><?php echo esc_html( $de
					? 'Es wird ein Entwurf angelegt – nichts wird sofort veröffentlicht.'
					: 'A draft is created – nothing will be published immediately.' ); ?></p>
			</div>

			<?php // Step-by-step guide. ?>
			<div class="wk-guide-steps">
				<h2><?php echo esc_html( $de ? 'Schritt-für-Schritt-Anleitung' : 'Step-by-step guide' ); ?></h2>
				<ol>
					<li>
						<strong><?php echo esc_html( $de ? 'Seite anlegen' : 'Create a page' ); ?></strong><br>
						<?php echo esc_html( $de
							? 'Nutze den Schnellstart-Button oben oder lege manuell eine neue WordPress-Seite an (z. B. „Widerruf").'
							: 'Use the quick-start button above, or manually create a new WordPress page (e.g. "Withdrawal").' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html( $de ? 'Händlerangaben ergänzen' : 'Add merchant details' ); ?></strong><br>
						<?php echo esc_html( $de
							? 'Füge deine Pflichtangaben (Name/Firma, Anschrift, E-Mail) oberhalb des Formulars ein. Diese Angaben sind gesetzlich vorgeschrieben.'
							: 'Add your mandatory details (name/company, address, email) above the form. These are legally required.' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html( $de ? 'Widerrufsformular-Block einsetzen' : 'Insert the withdrawal form block' ); ?></strong><br>
						<?php echo esc_html( $de
							? 'Suche im Block-Editor nach „Widerrufsformular" (Block aus diesem Plugin). Vertragstyp und Felder im Inspector anpassen.'
							: 'Search for "Withdrawal Form" in the block editor (block from this plugin). Adjust contract type and fields in the inspector.' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html( $de ? 'E-Mail-Versand konfigurieren' : 'Configure email delivery' ); ?></strong><br>
						<?php
						$mail_url = admin_url( 'admin.php?page=widerruf-kontakt-mail' );
						echo wp_kses(
							$de
								? sprintf( 'Öffne <a href="%s">E-Mail-Versand</a> und richte SMTP ein (Brevo empfohlen). Sonst kann kein Widerruf ankommen.', esc_url( $mail_url ) )
								: sprintf( 'Open <a href="%s">Email delivery</a> and set up SMTP (Brevo recommended). Without this, no withdrawals will arrive.', esc_url( $mail_url ) ),
							array( 'a' => array( 'href' => array() ) )
						);
						?>
					</li>
					<li>
						<strong><?php echo esc_html( $de ? 'Seite veröffentlichen' : 'Publish the page' ); ?></strong><br>
						<?php echo esc_html( $de
							? 'Prüfe die Seite im Frontend, dann veröffentlichen.'
							: 'Review the page in the frontend, then publish.' ); ?>
					</li>
					<li>
						<strong><?php echo esc_html( $de ? 'Footer-Verlinkung setzen' : 'Add a footer link' ); ?></strong><br>
						<?php echo esc_html( $de
							? 'Verlinke die Widerrufsseite im Footer deines Themes. Im Full-Site-Editor: Design → Editor → Footer-Part → Navigation bearbeiten → Link auf /widerruf hinzufügen. Linktext: „Widerrufsrecht" oder „Widerruf".'
							: 'Link the withdrawal page in your theme\'s footer. In the Full Site Editor: Appearance → Editor → Footer template part → Edit Navigation → Add link to /withdrawal. Link text: "Right of withdrawal" or "Withdraw".' ); ?>
					</li>
				</ol>
			</div>

			<?php // EU withdrawal-function note (from 2026-06-19). ?>
			<div class="wk-guide-eu-note">
				<h2><?php echo esc_html( $de ? '📋 EU-Widerrufsfunktion (ab 19. Juni 2026)' : '📋 EU withdrawal function (from 19 June 2026)' ); ?></h2>
				<p><?php echo esc_html( $de
					? 'Die EU-Richtlinie über Finanzdienstleistungen im Fernabsatz (2023/2673/EU) verlangt ab dem 19. Juni 2026 für Fernabsatz-Finanzdienstleistungen eine deutlich zugängliche „Widerrufsfunktion". Empfehlung: Setze einen gut sichtbaren Footer-Link oder Button mit dem Text „Vertrag widerrufen" bzw. „Widerrufsrecht nutzen", der direkt auf deine Widerrufsseite führt. Dies gilt insbesondere für Online-Vertragsabschlüsse (z. B. digitale Produkte mit Abonnement, Finanzprodukte).'
					: 'The EU Directive on distance financial services (2023/2673/EU) requires, from 19 June 2026, a clearly accessible "withdrawal function" for distance financial services. Recommendation: add a prominently visible footer link or button labelled "Withdraw contract" or "Exercise right of withdrawal", pointing directly to your withdrawal page. This is especially relevant for online contracts (e.g. digital subscription products, financial products).' ); ?></p>
			</div>

			<?php // Important notes. ?>
			<div class="wk-guide-notes">
				<h2><?php echo esc_html( $de ? 'Wichtige Hinweise' : 'Important notes' ); ?></h2>
				<ul>
					<li><?php echo esc_html( $de ? 'Pflichtangaben des Unternehmers müssen oberhalb des Formulars stehen.' : 'Mandatory merchant details must appear above the form.' ); ?></li>
					<li><?php echo esc_html( $de ? 'Der Absender erhält automatisch eine Eingangsbestätigung per E-Mail.' : 'The sender automatically receives a confirmation of receipt by email.' ); ?></li>
					<li><?php echo esc_html( $de ? 'Keine Datenbankprotokollierung der Einreichungen (DSGVO-Datensparsamkeit).' : 'No database logging of submissions (GDPR data minimisation).' ); ?></li>
					<li><?php echo esc_html( $de ? 'Spam-Schutz: Honeypot + IP-Rate-Limit sind aktiv.' : 'Spam protection: honeypot + IP rate limit are active.' ); ?></li>
					<li><?php echo esc_html( $de ? 'Das Kontaktformular-Block ist ebenfalls verfügbar (Block „Kontaktformular" im Editor suchen).' : 'The contact form block is also available (search for "Contact Form" in the editor).' ); ?></li>
				</ul>
			</div>

			<?php // Disclaimer. ?>
			<div class="wk-guide-disclaimer">
				<p><?php echo esc_html( $de
					? '⚠️ Kein Rechtsrat: Dieses Plugin stellt ein technisches Werkzeug bereit. Widerrufsbelehrung, AGB und rechtliche Anforderungen sind mit einem Anwalt oder einem spezialisierten Rechtsdienst (z. B. IT-Recht Kanzlei, e-recht24) abzustimmen.'
					: '⚠️ No legal advice: This plugin provides a technical tool. The right-of-withdrawal notice, terms and conditions and legal requirements must be verified with a lawyer or a specialised legal service.' ); ?></p>
			</div>
		</div>
		<?php
		$this->print_guide_styles();
	}

	private function print_guide_styles() {
		?>
		<style>
		.wk-guide { max-width: 860px; }
		.wk-guide-version { color: #646970; font-size: 12px; margin-top: -8px; margin-bottom: 1.5rem; }
		.wk-guide-cta, .wk-guide-steps, .wk-guide-eu-note, .wk-guide-notes, .wk-guide-disclaimer {
			background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;
			padding: 1.25rem 1.5rem; margin-bottom: 1.25rem;
		}
		.wk-guide-cta { border-color: #2271b1; }
		.wk-guide-cta h2 { margin-top: 0; }
		.wk-guide-cta .button-hero { margin: .5rem 0; font-size: 15px !important; }
		.wk-guide-cta .description { margin-top: .4rem; color: #646970; }
		.wk-guide-steps h2, .wk-guide-eu-note h2, .wk-guide-notes h2 { margin-top: 0; }
		.wk-guide-steps ol { margin: .75rem 0 0 1.2rem; }
		.wk-guide-steps li { margin-bottom: .9rem; line-height: 1.55; }
		.wk-guide-notes ul { margin: .5rem 0 0 1.2rem; }
		.wk-guide-notes li { margin-bottom: .4rem; line-height: 1.5; }
		.wk-guide-disclaimer { background: #fcf9e8; border-color: #dba617; }
		.wk-guide-disclaimer p { margin: 0; line-height: 1.55; }
		.wk-guide-eu-note { background: #f0f6fc; border-color: #72aee6; }
		.wk-guide-eu-note p { margin: 0; line-height: 1.55; }
		</style>
		<?php
	}
}
