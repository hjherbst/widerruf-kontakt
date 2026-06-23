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

	const OPT_PAGE_ID = 'wk_revocation_page_id';

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_wk_create_revocation_page', array( $this, 'handle_create_page' ) );
		add_action( 'admin_notices', array( $this, 'maybe_smtp_notice' ) );
	}

	public function register_menu() {
		$de = wk_is_de();
		add_menu_page(
			$de ? 'Widerruf & Kontakt' : 'Widerruf & Kontakt',
			'Widerruf',
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

	/**
	 * Returns the existing withdrawal page (or null) using a stored ID first,
	 * then falling back to the known slugs. Trashed pages are ignored.
	 *
	 * @return WP_Post|null
	 */
	public function get_existing_page() {
		$stored_id = (int) get_option( self::OPT_PAGE_ID, 0 );
		if ( $stored_id > 0 ) {
			$post = get_post( $stored_id );
			if ( $post instanceof WP_Post && 'page' === $post->post_type && 'trash' !== $post->post_status ) {
				return $post;
			}
		}

		foreach ( array( 'widerruf', 'withdrawal' ) as $slug ) {
			$post = get_page_by_path( $slug );
			if ( $post instanceof WP_Post && 'trash' !== $post->post_status ) {
				update_option( self::OPT_PAGE_ID, $post->ID );
				return $post;
			}
		}

		return null;
	}

	public function handle_create_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorised' );
		}
		check_admin_referer( 'wk_create_revocation_page' );

		// If a page already exists, link to it instead of creating a duplicate.
		$existing = $this->get_existing_page();
		if ( $existing ) {
			wp_safe_redirect( get_edit_post_link( $existing->ID, 'raw' ) );
			exit;
		}

		$de      = wk_is_de();
		$title   = $de ? 'Widerruf' : 'Right of Withdrawal';
		$slug    = $de ? 'widerruf' : 'withdrawal';
		$content = $this->build_page_content( $de );

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

		update_option( self::OPT_PAGE_ID, (int) $page_id );
		wp_safe_redirect( get_edit_post_link( $page_id, 'raw' ) );
		exit;
	}

	private function build_page_content( $de ) {
		// Draft page: a full-width group with a heading, a short intro and the
		// withdrawal form (set to wide alignment) – ready to edit.
		if ( $de ) {
			$heading = 'Vertrag widerrufen';
			$intro   = 'Online geschlossene Verträge können innerhalb von 14 Tagen widerrufen werden. Bitte das Formular ausfüllen, um den Widerruf einzureichen.';
		} else {
			$heading = 'Withdraw from contract';
			$intro   = 'Contracts concluded online can be withdrawn within 14 days. Please fill in the form to submit your withdrawal.';
		}

		$form_id = 'wkrf-' . wp_generate_password( 8, false, false );

		return '<!-- wp:group {"metadata":{"name":"widerruf"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->' . "\n"
			. '<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--30)"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->' . "\n"
			. '<div class="wp-block-group alignwide"><!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->' . "\n"
			. '<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:heading {"level":1,"fontSize":"x-large"} -->' . "\n"
			. '<h1 class="wp-block-heading has-x-large-font-size">' . esc_html( $heading ) . '</h1>' . "\n"
			. '<!-- /wp:heading -->' . "\n\n"
			. '<!-- wp:paragraph {"fontSize":"small"} -->' . "\n"
			. '<p class="has-small-font-size">' . esc_html( $intro ) . '</p>' . "\n"
			. '<!-- /wp:paragraph --></div>' . "\n"
			. '<!-- /wp:group -->' . "\n\n"
			. '<!-- wp:widerruf-kontakt/revocation-form {"formId":"' . esc_attr( $form_id ) . '","align":"wide"} /--></div>' . "\n"
			. '<!-- /wp:group --></div>' . "\n"
			. '<!-- /wp:group -->';
	}

	// -------------------------------------------------------------------------
	// Guide page
	// -------------------------------------------------------------------------

	/**
	 * Show a warning on plugin admin screens when SMTP is not configured yet.
	 */
	public function maybe_smtp_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( $screen->id, 'widerruf-kontakt' ) ) {
			return;
		}

		if ( WK_Mailer::smtp_configured() ) {
			return;
		}

		$de       = wk_is_de();
		$mail_url = admin_url( 'admin.php?page=widerruf-kontakt-mail' );
		$on_mail  = ( 'widerruf-kontakt_page_widerruf-kontakt-mail' === $screen->id );

		if ( $on_mail ) {
			$title = $de ? 'E-Mail-Versand noch nicht eingerichtet' : 'Email delivery not configured yet';
			$body  = $de
				? 'Formulare können erst zuverlässig versendet werden, wenn SMTP-Daten hinterlegt sind. Bitte unten einen Versandweg wählen, Zugangsdaten eintragen und speichern.'
				: 'Forms can only be sent reliably once SMTP credentials are saved. Choose a sending method below, enter your credentials and save.';
		} else {
			$title = $de ? 'E-Mail-Versand noch nicht eingerichtet' : 'Email delivery not configured yet';
			$body  = $de
				? sprintf(
					'Ohne SMTP-Einstellungen kommen eingereichte Formulare nicht zuverlässig an. Bitte unter <a href="%s">E-Mail-Versand</a> Brevo, ein Postfach oder manuelles SMTP einrichten.',
					esc_url( $mail_url )
				)
				: sprintf(
					'Without SMTP settings, submitted forms will not arrive reliably. Set up Brevo, a mailbox or manual SMTP under <a href="%s">Email delivery</a>.',
					esc_url( $mail_url )
				);
		}
		?>
		<div class="notice notice-warning" style="border-left-color:#dba617;padding:12px 16px;">
			<p style="margin:0;font-size:14px;line-height:1.5;">
				<strong style="display:block;margin-bottom:4px;"><?php echo esc_html( $title ); ?></strong>
				<?php
				if ( $on_mail ) {
					echo esc_html( $body );
				} else {
					echo wp_kses( $body, array( 'a' => array( 'href' => array() ) ) );
				}
				?>
			</p>
		</div>
		<?php
	}

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
				<h2><?php echo esc_html( $de ? 'Schnellstart' : 'Quick start' ); ?></h2>
				<?php
				$existing_page = $this->get_existing_page();
				if ( $existing_page ) :
					$edit_url   = get_edit_post_link( $existing_page->ID );
					$view_url   = get_permalink( $existing_page->ID );
					$is_draft   = 'draft' === $existing_page->post_status;
					?>
					<p><?php echo esc_html( $de
						? 'Die Widerrufsseite wurde bereits angelegt. Du kannst sie direkt bearbeiten oder ansehen.'
						: 'The withdrawal page already exists. You can edit or view it directly.' ); ?></p>
					<p>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary button-hero">
							<?php echo esc_html( $de ? 'Widerrufsseite bearbeiten' : 'Edit withdrawal page' ); ?>
						</a>
						<?php if ( $view_url ) : ?>
							<a href="<?php echo esc_url( $view_url ); ?>" class="button button-hero" target="_blank" rel="noopener noreferrer" style="margin-left:8px;">
								<?php echo esc_html( $de ? 'Seite ansehen' : 'View page' ); ?>
							</a>
						<?php endif; ?>
					</p>
					<p class="description"><?php echo esc_html( $de
						? ( $is_draft ? 'Status: Entwurf – noch nicht veröffentlicht.' : 'Status: veröffentlicht.' )
						: ( $is_draft ? 'Status: draft – not published yet.' : 'Status: published.' ) ); ?></p>
				<?php else : ?>
					<p><?php echo esc_html( $de
						? 'Klicke auf den Button, um sofort eine Entwurfsseite mit dem Widerrufsformular und dem gesetzlich empfohlenen Mustertext anzulegen.'
						: 'Click the button to immediately create a draft page with the withdrawal form and the legally recommended model text.' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wk_create_revocation_page" />
						<?php wp_nonce_field( 'wk_create_revocation_page' ); ?>
						<button type="submit" class="button button-primary button-hero">
							<?php echo esc_html( $de ? 'Widerrufsseite erstellen' : 'Create withdrawal page' ); ?>
						</button>
					</form>
					<p class="description"><?php echo esc_html( $de
						? 'Es wird ein Entwurf angelegt – nichts wird sofort veröffentlicht.'
						: 'A draft is created – nothing will be published immediately.' ); ?></p>
				<?php endif; ?>
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

			<?php // Important notes. ?>
			<div class="wk-guide-notes">
				<h2><?php echo esc_html( $de ? 'Wichtige Hinweise' : 'Important notes' ); ?></h2>
				<ul>
					<li><?php echo esc_html( $de ? 'Die Eingangsbestätigung an den Absender enthält Datum und Uhrzeit des Eingangs (Serverseitig, Seitenzeitzone).' : 'The confirmation email to the sender includes the exact server-side date and time of receipt.' ); ?></li>
					<li><?php echo esc_html( $de ? 'Keine Datenbankprotokollierung der Einreichungen (DSGVO-Datensparsamkeit).' : 'No database logging of submissions (GDPR data minimisation).' ); ?></li>
					<li><?php echo esc_html( $de ? 'Spam-Schutz: Honeypot + IP-Rate-Limit sind aktiv.' : 'Spam protection: honeypot + IP rate limit are active.' ); ?></li>
					<li><?php echo esc_html( $de ? 'Das Kontaktformular-Block ist ebenfalls verfügbar (Block „Kontaktformular" im Editor suchen).' : 'The contact form block is also available (search for "Contact Form" in the editor).' ); ?></li>
				</ul>
			</div>

			<?php // Disclaimer. ?>
			<div class="wk-guide-disclaimer">
				<p><?php echo esc_html( $de
					? '⚠️ Kein Rechtsrat: Dieses Plugin stellt ein technisches Werkzeug bereit. Widerrufsbelehrung, AGB und rechtliche Anforderungen sind mit einem Anwalt abzustimmen.'
					: '⚠️ No legal advice: This plugin provides a technical tool. The right-of-withdrawal notice, terms and conditions and legal requirements must be verified with a lawyer.' ); ?></p>
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
		.wk-guide-cta, .wk-guide-steps, .wk-guide-notes, .wk-guide-disclaimer {
			background: #fff; border: 1px solid #c3c4c7; border-radius: 6px;
			padding: 1.25rem 1.5rem; margin-bottom: 1.25rem;
		}
		.wk-guide-cta { border-color: #2271b1; }
		.wk-guide-cta h2 { margin-top: 0; }
		.wk-guide-cta .button-hero { margin: .5rem 0; font-size: 15px !important; }
		.wk-guide-cta .description { margin-top: .4rem; color: #646970; }
		.wk-guide-steps h2, .wk-guide-notes h2 { margin-top: 0; }
		.wk-guide-steps ol { margin: .75rem 0 0 1.2rem; }
		.wk-guide-steps li { margin-bottom: .9rem; line-height: 1.55; }
		.wk-guide-notes ul { margin: .5rem 0 0 1.2rem; }
		.wk-guide-notes li { margin-bottom: .4rem; line-height: 1.5; }
		.wk-guide-disclaimer { background: #fcf9e8; border-color: #dba617; }
		.wk-guide-disclaimer p { margin: 0; line-height: 1.55; }
		</style>
		<?php
	}
}
