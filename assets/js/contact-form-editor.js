/**
 * Widerruf & Kontakt – Contact Form Block editor registration (no build step).
 *
 * Dynamic block: PHP render_callback handles the frontend output.
 * The editor shows a live preview and inspector controls.
 */
( function( wp ) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var useEffect = wp.element.useEffect;

	var i18n = window.wkContactFormEditor || {};

	var keywords = i18n.lang === 'de'
		? [ 'Kontakt', 'Kontaktformular', 'Nachricht' ]
		: [ 'Contact', 'Contact form', 'Message' ];

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;

		useEffect( function() {
			if ( ! a.formId ) {
				set( { formId: 'wkcf-' + Math.random().toString( 36 ).slice( 2, 10 ) } );
			}
		}, [] );

		var blockProps = useBlockProps( { className: 'wk-contact-form-wrap' } );
		var reqMark    = el( 'span', { className: 'wk-cf-req' }, ' *' );
		var cols       = ( a.showName ? 1 : 0 ) + 1 + ( a.showPhone ? 1 : 0 );

		var inspector = el( InspectorControls, {},
			el( PanelBody, { title: i18n.panelFields, initialOpen: true },
				el( ToggleControl, {
					label: i18n.showName,
					checked: !! a.showName,
					onChange: function( v ) { set( { showName: v } ); },
					__nextHasNoMarginBottom: true,
				} ),
				a.showName && el( ToggleControl, {
					label: i18n.nameRequired,
					checked: !! a.nameRequired,
					onChange: function( v ) { set( { nameRequired: v } ); },
					__nextHasNoMarginBottom: true,
				} ),
				el( ToggleControl, {
					label: i18n.showPhone,
					checked: !! a.showPhone,
					onChange: function( v ) { set( { showPhone: v } ); },
					__nextHasNoMarginBottom: true,
				} ),
				a.showPhone && el( ToggleControl, {
					label: i18n.phoneRequired,
					checked: !! a.phoneRequired,
					onChange: function( v ) { set( { phoneRequired: v } ); },
					__nextHasNoMarginBottom: true,
				} ),
				el( 'p', { style: { fontSize: '12px', color: '#757575', marginTop: '8px' } }, i18n.fieldsNote )
			),
			el( PanelBody, { title: i18n.panelConsent, initialOpen: false },
				el( ToggleControl, {
					label: i18n.showConsent,
					checked: !! a.showConsent,
					onChange: function( v ) { set( { showConsent: v } ); },
					__nextHasNoMarginBottom: true,
				} ),
				a.showConsent && el( 'p', { style: { fontSize: '12px', color: '#757575', marginTop: '8px' } }, i18n.consentEditHint )
			),
			el( PanelBody, { title: i18n.panelTexts, initialOpen: false },
				el( TextControl, {
					label: i18n.submitLabel,
					value: a.submitLabel || '',
					onChange: function( v ) { set( { submitLabel: v } ); },
					placeholder: i18n.submit,
					help: i18n.submitHelp,
					__nextHasNoMarginBottom: true,
				} ),
				el( TextareaControl, {
					label: i18n.successLabel,
					value: a.successMessage || '',
					onChange: function( v ) { set( { successMessage: v } ); },
					placeholder: i18n.success,
					help: i18n.successHelp,
					rows: 3,
					__nextHasNoMarginBottom: true,
				} )
			)
		);

		// Editor preview mirroring the PHP render markup.
		var topFields = [];
		if ( a.showName ) {
			topFields.push( el( 'p', { className: 'wk-cf-field', key: 'name' },
				el( 'label', {}, i18n.name, a.nameRequired && reqMark ),
				el( 'input', { type: 'text', disabled: true } )
			) );
		}
		topFields.push( el( 'p', { className: 'wk-cf-field', key: 'email' },
			el( 'label', {}, i18n.email, ' ', reqMark ),
			el( 'input', { type: 'email', disabled: true } )
		) );
		if ( a.showPhone ) {
			topFields.push( el( 'p', { className: 'wk-cf-field', key: 'phone' },
				el( 'label', {}, i18n.phone, a.phoneRequired && reqMark ),
				el( 'input', { type: 'tel', disabled: true } )
			) );
		}

		var preview = el( 'div', { className: 'wk-contact-form' },
			el( 'div', { className: 'wk-cf-row wk-cf-row--fields', 'data-cols': cols }, topFields ),
			el( 'div', { className: 'wk-cf-row' },
				el( 'p', { className: 'wk-cf-field' },
					el( 'label', {}, i18n.message, ' ', reqMark ),
					el( 'textarea', { rows: 6, disabled: true } )
				)
			),
			a.showConsent && el( 'div', { className: 'wk-cf-row wk-cf-consent' },
				el( 'label', { className: 'wk-cf-consent-label' },
					el( 'input', { type: 'checkbox', disabled: true } ),
					el( RichText, {
						tagName: 'span',
						className: 'wk-cf-consent-text',
						value: a.consentHtml,
						onChange: function( v ) { set( { consentHtml: v } ); },
						placeholder: i18n.consent,
						allowedFormats: [ 'core/link' ],
					} )
				)
			),
			el( 'div', { className: 'wk-cf-row wk-cf-submit-row wp-block-buttons' },
				el( 'div', { className: 'wp-block-button' },
					el( 'button', { type: 'button', className: 'wk-cf-submit wp-block-button__link wp-element-button', disabled: true },
						a.submitLabel || i18n.submit
					)
				)
			)
		);

		return el( Fragment, {},
			inspector,
			el( 'div', blockProps, preview )
		);
	}

	registerBlockType( 'widerruf-kontakt/contact-form', {
		apiVersion: 2,
		title: i18n.blockTitle || 'Contact Form',
		description: i18n.blockDescription || '',
		category: 'design',
		keywords: keywords,
		icon: 'email',
		supports: {
			align: [ 'wide', 'full' ],
			html: false,
		},
		attributes: {
			showName:       { type: 'boolean', default: true },
			nameRequired:   { type: 'boolean', default: false },
			showPhone:      { type: 'boolean', default: true },
			phoneRequired:  { type: 'boolean', default: false },
			showConsent:    { type: 'boolean', default: true },
			consentHtml:    { type: 'string',  default: '' },
			submitLabel:    { type: 'string',  default: '' },
			successMessage: { type: 'string',  default: '' },
			formId:         { type: 'string',  default: '' },
		},
		edit: Edit,
		save: function() { return null; },
	} );
} )( window.wp );
