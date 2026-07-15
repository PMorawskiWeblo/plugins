( function ( $ ) {
	'use strict';

	function getConfig() {
		return window.fastFormsPublic || {};
	}

	function openModal( instanceId ) {
		if ( ! instanceId ) {
			return;
		}

		var $wrap = $( '#' + $.escapeSelector( instanceId ) );
		if ( ! $wrap.length ) {
			return;
		}
		$wrap.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'ff-modal-open' );
	}

	function closeModal( $wrap ) {
		$wrap.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
		if ( ! $( '.ff-form-wrap.is-open' ).length ) {
			$( 'body' ).removeClass( 'ff-modal-open' );
		}
	}

	function getValidationMessages( $form, $wrap ) {
		var $json = $form.find( 'script.ff-form-validation-data' ).first();

		if ( $json.length ) {
			try {
				return JSON.parse( $json.text() );
			} catch ( e ) {
				// Fallback below.
			}
		}

		var raw = $form.attr( 'data-ff-validation' ) || $wrap.attr( 'data-ff-validation' ) || '{}';

		try {
			return JSON.parse( raw );
		} catch ( e ) {
			return {};
		}
	}

	function getFieldValue( $field ) {
		var $multipleChoice = $field.find( '.ff-choice-group--multiple input[type="checkbox"]' );

		if ( $multipleChoice.length ) {
			var values = [];
			$multipleChoice.filter( ':checked' ).each( function () {
				values.push( String( $( this ).val() || '' ) );
			} );
			return values;
		}

		var $radios = $field.find( 'input[type="radio"]' );

		if ( $radios.length ) {
			var $checked = $radios.filter( ':checked' );
			return $checked.length ? String( $checked.val() || '' ) : '';
		}

		var $starInput = $field.find( 'input.ff-input--star-rating' );

		if ( $starInput.length ) {
			return $.trim( String( $starInput.val() || '' ) );
		}

		var $inputs = $field.find( 'input, textarea, select' ).not( '[type=hidden]' );

		if ( ! $inputs.length ) {
			return '';
		}

		var $input = $inputs.first();
		var type = ( $input.attr( 'type' ) || '' ).toLowerCase();

		if ( type === 'checkbox' ) {
			return $input.is( ':checked' ) ? '1' : '';
		}

		if ( type === 'file' ) {
			var $wrap = $field.find( '.ff-file-upload' );
			var files = getStoredFiles( $wrap );

			if ( ! files.length ) {
				return '';
			}

			return files.map( function ( file ) {
				return file.name;
			} ).join( ', ' );
		}

		return $.trim( String( $input.val() || '' ) );
	}

	var fileIconSvg = '<svg class="ff-icon ff-icon--file" width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true" focusable="false"><path d="M10.5 2H4.5C3.675 2 3 2.675 3 3.5V14.5C3 15.325 3.675 16 4.5 16H13.5C14.325 16 15 15.325 15 14.5V6.5L10.5 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.5 2V6.5H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	var trashIconSvg = '<svg class="ff-icon ff-icon--trash" width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M6 2h8l1 2h4v2H3V4h4l1-2zm1 6h2v7H7V8zm4 0h2v7h-2V8zM5 18h10a2 2 0 0 0 2-2V8H3v8a2 2 0 0 0 2 2z"/></svg>';

	function getFileUploadConfig( $wrap ) {
		var $input = $wrap.find( 'input.ff-input--file' );

		return {
			$input: $input,
			allowMultiple: $wrap.attr( 'data-ff-multiple' ) === '1' || $input.attr( 'data-ff-multiple' ) === '1' || $input.prop( 'multiple' ),
			minFiles: parseInt( $wrap.attr( 'data-ff-min-files' ) || $input.attr( 'data-ff-min-files' ), 10 ) || 0,
			maxFiles: parseInt( $wrap.attr( 'data-ff-max-files' ) || $input.attr( 'data-ff-max-files' ), 10 ) || 0,
			maxKb: parseInt( $input.attr( 'data-ff-max-size' ), 10 ) || 0,
			allowed: ( $input.attr( 'data-ff-allowed-types' ) || '' ).toLowerCase(),
		};
	}

	function fileMatchesAllowed( file, allowed ) {
		if ( ! allowed ) {
			return true;
		}

		var ext = file.name.split( '.' ).pop().toLowerCase();
		var allowedList = allowed.split( ',' ).map( function ( item ) {
			return item.trim().replace( /^\./, '' );
		} );

		return allowedList.indexOf( ext ) !== -1;
	}

	function formatMaxFileSizeLabel( maxKb ) {
		if ( maxKb >= 1024 ) {
			var mb = maxKb / 1024;

			return ( mb % 1 === 0 ? String( mb ) : mb.toFixed( 1 ) ) + ' MB';
		}

		return maxKb + ' KB';
	}

	function getFileTooLargeMessage( config, messages ) {
		var i18n = getConfig().i18n || {};
		var base = messages.fileTooLarge || i18n.fileTooLarge || 'The file is too large.';

		if ( config.maxKb > 0 ) {
			return base + ' ' + ( i18n.fileMaxSize || 'Maksymalny rozmiar:' ) + ' ' + formatMaxFileSizeLabel( config.maxKb ) + '.';
		}

		return base;
	}

	function validateSingleFile( file, config, messages ) {
		if ( config.maxKb > 0 && file.size > config.maxKb * 1024 ) {
			return getFileTooLargeMessage( config, messages );
		}

		if ( ! fileMatchesAllowed( file, config.allowed ) ) {
			return messages.invalidFile || getConfig().i18n?.invalidFile || 'Niedozwolony typ pliku.';
		}

		return '';
	}

	function filesFromInput( input ) {
		if ( ! input || ! input.files ) {
			return [];
		}

		return Array.prototype.slice.call( input.files );
	}

	function syncInputFiles( input, files ) {
		if ( ! input || typeof DataTransfer === 'undefined' ) {
			return;
		}

		var transfer = new DataTransfer();

		files.forEach( function ( file ) {
			transfer.items.add( file );
		} );

		input.files = transfer.files;
	}

	function mergeSelectedFiles( currentFiles, newFiles, config ) {
		var merged = config.allowMultiple ? currentFiles.slice() : [];
		var limit = config.maxFiles > 0 ? config.maxFiles : 0;

		newFiles.forEach( function ( file ) {
			var exists = merged.some( function ( existing ) {
				return existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified;
			} );

			if ( exists ) {
				return;
			}

			if ( ! config.allowMultiple ) {
				merged = [ file ];
				return;
			}

			if ( limit > 0 && merged.length >= limit ) {
				return;
			}

			merged.push( file );
		} );

		if ( ! config.allowMultiple && merged.length > 1 ) {
			merged = merged.slice( 0, 1 );
		}

		if ( limit > 0 && merged.length > limit ) {
			merged = merged.slice( 0, limit );
		}

		return merged;
	}

	function renderFileUploadList( $wrap, files ) {
		var i18n = getConfig().i18n || {};
		var $list = $wrap.find( '.ff-file-upload__list' );
		var $empty = $wrap.find( '.ff-file-upload__empty' );
		var removeLabel = i18n.removeFile || 'Remove file';

		$list.empty();

		if ( ! files.length ) {
			$list.prop( 'hidden', true );
			$empty.prop( 'hidden', false ).text( i18n.noFileSelected || 'No file selected' );
			return;
		}

		$empty.prop( 'hidden', true );
		$list.prop( 'hidden', false );
		$list.toggleClass( 'ff-file-upload__list--horizontal', $wrap.hasClass( 'ff-file-upload--horizontal' ) );

		files.forEach( function ( file, index ) {
			var $item = $( '<li>', { class: 'ff-file-upload__item' } );
			var $icon = $( '<span>', { class: 'ff-file-upload__item-icon', html: fileIconSvg } );
			var $name = $( '<span>', { class: 'ff-file-upload__item-name', text: file.name } );
			var $remove = $( '<button>', {
				type: 'button',
				class: 'ff-file-upload__item-remove',
				'aria-label': removeLabel,
				title: removeLabel,
				html: trashIconSvg,
				'data-file-index': index,
			} );

			$item.append( $icon, $name, $remove );
			$list.append( $item );
		} );
	}

	function getStoredFiles( $wrap ) {
		var stored = $wrap.data( 'ff-files' );

		if ( Array.isArray( stored ) ) {
			return stored.slice();
		}

		var config = getFileUploadConfig( $wrap );
		return filesFromInput( config.$input[ 0 ] );
	}

	function setStoredFiles( $wrap, files ) {
		$wrap.data( 'ff-files', files.slice() );

		var config = getFileUploadConfig( $wrap );
		syncInputFiles( config.$input[ 0 ], files );
		renderFileUploadList( $wrap, files );
	}

	function updateFileUploadDisplay( $wrap ) {
		var files = getStoredFiles( $wrap );
		setStoredFiles( $wrap, files );
	}

	function validateFileField( $field, messages ) {
		var $wrap = $field.find( '.ff-file-upload' );
		var config = getFileUploadConfig( $wrap );
		var required = $field.attr( 'data-ff-required' ) === '1';
		var files = getStoredFiles( $wrap );
		var i18n = getConfig().i18n || {};

		if ( ! files.length ) {
			return required ? ( messages.required || i18n.required || 'This field is required.' ) : '';
		}

		var minFiles = config.minFiles;

		if ( ( isNaN( minFiles ) || minFiles <= 0 ) && required && config.allowMultiple ) {
			minFiles = 1;
		}

		if ( config.allowMultiple && minFiles > 0 && files.length < minFiles ) {
			return ( i18n.tooFewFiles || 'Upload at least the minimum number of files.' ) + ' (' + minFiles + ')';
		}

		if ( config.maxFiles > 0 && files.length > config.maxFiles ) {
			return ( i18n.tooManyFiles || 'Too many files selected.' ) + ' (' + config.maxFiles + ')';
		}

		for ( var i = 0; i < files.length; i++ ) {
			var fileError = validateSingleFile( files[ i ], config, messages );

			if ( fileError ) {
				return fileError;
			}
		}

		return '';
	}

	function initFileUploadFields( $context ) {
		( $context || $( document ) ).find( '.ff-file-upload' ).each( function () {
			updateFileUploadDisplay( $( this ) );
		} );
	}

	function syncAllFileUploads( $form ) {
		$form.find( '.ff-file-upload' ).each( function () {
			var $wrap = $( this );
			setStoredFiles( $wrap, getStoredFiles( $wrap ) );
		} );
	}

	function resetFileUploadFields( $form ) {
		$form.find( '.ff-file-upload' ).each( function () {
			var $wrap = $( this );
			$wrap.removeData( 'ff-files' );
			setStoredFiles( $wrap, [] );
		} );
	}

	function validateChoiceField( $field, messages ) {
		var allowMultiple = $field.attr( 'data-ff-allow-multiple' ) === '1';

		if ( ! allowMultiple ) {
			return '';
		}

		var count = $field.find( '.ff-choice-group--multiple input[type="checkbox"]:checked' ).length;
		var required = $field.attr( 'data-ff-required' ) === '1';
		var min = parseInt( $field.attr( 'data-ff-min-selections' ), 10 );
		var max = parseInt( $field.attr( 'data-ff-max-selections' ), 10 );

		if ( isNaN( min ) && required ) {
			min = 1;
		}

		if ( required && count === 0 ) {
			return messages.required || getConfig().i18n?.required || 'This field is required.';
		}

		if ( ! isNaN( min ) && count < min ) {
			var minTpl = getConfig().i18n?.minSelections || 'Select at least %d option(s).';
			return minTpl.replace( '%d', String( min ) );
		}

		if ( ! isNaN( max ) && count > max ) {
			var maxTpl = getConfig().i18n?.maxSelections || 'Select at most %d option(s).';
			return maxTpl.replace( '%d', String( max ) );
		}

		return '';
	}

	function validateClient( $form, $wrap ) {
		var messages = getValidationMessages( $form, $wrap );
		var errors = {};

		$form.find( '.ff-field' ).not( '.ff-field--submit, .ff-field--content' ).each( function () {
			var $field = $( this );
			var key = $field.attr( 'data-ff-field-key' );

			if ( ! key ) {
				return;
			}

			var required = $field.attr( 'data-ff-required' ) === '1';
			var value = getFieldValue( $field );

			if ( $field.hasClass( 'ff-field--file' ) ) {
				var fileError = validateFileField( $field, messages );
				if ( fileError ) {
					errors[ key ] = fileError;
				}
				return;
			}

			if ( $field.hasClass( 'ff-field--radio' ) && $field.attr( 'data-ff-allow-multiple' ) === '1' ) {
				var choiceError = validateChoiceField( $field, messages );
				if ( choiceError ) {
					errors[ key ] = choiceError;
				}
				return;
			}

			if ( required && '' === value ) {
				errors[ key ] = messages.required || getConfig().i18n?.required || 'This field is required.';
			}
		} );

		return errors;
	}

	function isFormClientValid( $form, $wrap ) {
		return ! Object.keys( validateClient( $form, $wrap ) ).length;
	}

	function updateSubmitLiveState( $form ) {
		var $btn = $form.find( '.ff-submit[data-ff-live-validation="1"]' ).first();

		if ( ! $btn.length || $form.hasClass( 'is-submitting' ) ) {
			return;
		}

		var $wrap = $form.closest( '.ff-form-wrap' );
		var isValid = isFormClientValid( $form, $wrap );

		$btn.prop( 'disabled', ! isValid );
		$btn.toggleClass( 'ff-submit--inactive', ! isValid );
	}

	function initSubmitLiveValidation( $context ) {
		( $context || $( document ) ).find( '.ff-form' ).each( function () {
			updateSubmitLiveState( $( this ) );
		} );
	}

	function clearFieldErrors( $form ) {
		$form.find( '.ff-field' ).removeClass( 'has-error' );
		$form.find( '.ff-field__error' ).remove();
	}

	function clearSingleFieldError( $field ) {
		$field.removeClass( 'has-error' );
		$field.find( '.ff-field__error' ).remove();
	}

	function showSingleFieldError( $field, message ) {
		if ( ! $field.length || ! message ) {
			return;
		}

		clearSingleFieldError( $field );
		$field.addClass( 'has-error' );
		$field.find( '.ff-field__control' ).append(
			$( '<div>', { class: 'ff-field__error', text: message, role: 'alert' } )
		);
	}

	function showFieldErrors( $form, errors ) {
		if ( ! errors ) {
			return;
		}

		Object.keys( errors ).forEach( function ( key ) {
			var $field = $form.find( '.ff-field[data-ff-field-key="' + key + '"]' );

			showSingleFieldError( $field, errors[ key ] );
		} );
	}

	function getFileFieldMessages( $field ) {
		var $form = $field.closest( 'form' );
		var $wrap = $field.closest( '.ff-form-wrap' );

		return getValidationMessages( $form, $wrap );
	}

	function applySelectedFiles( $wrap, selectedFiles ) {
		var $field = $wrap.closest( '.ff-field' );
		var config = getFileUploadConfig( $wrap );
		var messages = getFileFieldMessages( $field );
		var current = getStoredFiles( $wrap );
		var validNew = [];
		var errors = [];

		selectedFiles.forEach( function ( file ) {
			var fileError = validateSingleFile( file, config, messages );

			if ( fileError ) {
				if ( errors.indexOf( fileError ) === -1 ) {
					errors.push( fileError );
				}
				return;
			}

			validNew.push( file );
		} );

		var merged = mergeSelectedFiles( current, validNew, config );

		if ( config.maxFiles > 0 && merged.length > config.maxFiles ) {
			var tooManyMessage = ( getConfig().i18n?.tooManyFiles || 'Too many files selected.' ) + ' (' + config.maxFiles + ')';

			if ( errors.indexOf( tooManyMessage ) === -1 ) {
				errors.push( tooManyMessage );
			}

			merged = merged.slice( 0, config.maxFiles );
		}

		setStoredFiles( $wrap, merged );

		if ( errors.length ) {
			showSingleFieldError( $field, errors[ 0 ] );
			return;
		}

		var fieldError = validateFileField( $field, messages );

		if ( fieldError ) {
			showSingleFieldError( $field, fieldError );
			return;
		}

		clearSingleFieldError( $field );
	}

	function setSubmitting( $form, isSubmitting ) {
		var cfg = getConfig();
		var i18n = cfg.i18n || {};
		var $btn = $form.find( '.ff-submit' );
		var $preloader = $form.find( '.ff-form__preloader' );

		if ( isSubmitting ) {
			if ( $btn.length ) {
				if ( ! $btn.data( 'original-text' ) ) {
					$btn.data( 'original-text', $btn.text() );
				}
				$btn.prop( 'disabled', true ).text( $btn.attr( 'data-loading-text' ) || i18n.sending || 'Sending…' );
			}
			$form.addClass( 'is-submitting' );
			$preloader.prop( 'hidden', false );
		} else {
			if ( $btn.length ) {
				$btn.prop( 'disabled', false ).text( $btn.data( 'original-text' ) || $btn.text() );
			}
			$form.removeClass( 'is-submitting' );
			$preloader.prop( 'hidden', true );
			updateSubmitLiveState( $form );
		}
	}

	function showFormMessage( $messages, type, text ) {
		$messages.removeClass( 'is-success is-error' ).addClass( 'is-' + type ).empty();

		if ( text ) {
			$messages.append( $( '<p>' ).text( text ) );
		}

		if ( $messages.length && $messages[ 0 ].scrollIntoView ) {
			$messages[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}
	}

	function getSubmitUrl( $form ) {
		var url = $form.attr( 'data-ff-submit-url' );
		if ( url ) {
			return url;
		}

		var cfg = getConfig();
		var formId = $form.attr( 'data-ff-form' );

		if ( cfg.restUrl && formId ) {
			return cfg.restUrl.replace( /\/$/, '' ) + '/forms/' + formId + '/submit';
		}

		return '';
	}

	function formHasFiles( $form ) {
		var hasFileInput = $form.find( 'input[type="file"]' ).length > 0;

		if ( ! hasFileInput ) {
			return false;
		}

		return $form.find( 'input[type="file"]' ).filter( function () {
			return this.files && this.files.length;
		} ).length > 0;
	}

	function getCaptchaFieldName() {
		var cfg = getConfig();
		if ( cfg.captchaProvider === 'turnstile' ) {
			return 'cf-turnstile-response';
		}
		if ( cfg.captchaProvider === 'recaptcha' ) {
			return 'g-recaptcha-response';
		}
		return '';
	}

	function initTurnstileWidgets() {
		var cfg = getConfig();

		if ( cfg.captchaProvider !== 'turnstile' || ! cfg.turnstile || ! cfg.turnstile.siteKey || ! window.turnstile ) {
			return;
		}

		$( '.ff-turnstile-widget' ).each( function () {
			var $widget = $( this );

			if ( $widget.data( 'turnstile-ready' ) ) {
				return;
			}

			var widgetId = window.turnstile.render( this, {
				sitekey: cfg.turnstile.siteKey,
				size: 'invisible',
				callback: function ( token ) {
					var resolver = $widget.data( 'turnstile-resolver' );
					if ( resolver ) {
						resolver( token );
						$widget.removeData( 'turnstile-resolver' );
						$widget.removeData( 'turnstile-rejecter' );
					}
				},
				'error-callback': function () {
					var rejecter = $widget.data( 'turnstile-rejecter' );
					if ( rejecter ) {
						rejecter();
						$widget.removeData( 'turnstile-resolver' );
						$widget.removeData( 'turnstile-rejecter' );
					}
				},
			} );

			$widget.data( 'turnstile-widget-id', widgetId );
			$widget.data( 'turnstile-ready', true );
		} );
	}

	function obtainTurnstileToken( $form ) {
		return new Promise( function ( resolve, reject ) {
			var $widget = $form.find( '.ff-turnstile-widget' );

			if ( ! $widget.length || ! window.turnstile ) {
				reject();
				return;
			}

			var widgetId = $widget.data( 'turnstile-widget-id' );

			if ( ! widgetId ) {
				reject();
				return;
			}

			$widget.data( 'turnstile-resolver', resolve );
			$widget.data( 'turnstile-rejecter', reject );
			window.turnstile.reset( widgetId );
			window.turnstile.execute( widgetId );
		} );
	}

	function obtainCaptchaToken( $form ) {
		var cfg = getConfig();

		if ( cfg.captchaProvider === 'recaptcha' && cfg.recaptcha && cfg.recaptcha.siteKey && window.grecaptcha ) {
			return new Promise( function ( resolve, reject ) {
				window.grecaptcha.ready( function () {
					window.grecaptcha.execute( cfg.recaptcha.siteKey, { action: cfg.recaptcha.action } )
						.then( resolve )
						.catch( reject );
				} );
			} );
		}

		if ( cfg.captchaProvider === 'turnstile' && cfg.turnstile && cfg.turnstile.siteKey ) {
			return obtainTurnstileToken( $form );
		}

		return Promise.resolve( '' );
	}

	function buildSubmitRequest( $form, submitUrl, captchaToken, captchaField ) {
		var token = $form.find( 'input[name="ff_form_nonce"]' ).val() || '';
		var options = {
			url: submitUrl,
			type: 'POST',
			dataType: 'json',
			headers: {},
		};

		if ( token ) {
			options.headers['X-FF-Form-Nonce'] = token;
		}

		if ( formHasFiles( $form ) ) {
			options.data = new FormData( $form[ 0 ] );

			if ( captchaToken && captchaField ) {
				options.data.append( captchaField, captchaToken );
			}

			options.processData = false;
			options.contentType = false;
		} else {
			options.data = $form.serialize();

			if ( captchaToken && captchaField ) {
				options.data += '&' + $.param( ( function () {
					var payload = {};
					payload[ captchaField ] = captchaToken;
					return payload;
				} )() );
			}
		}

		return options;
	}

	function sendFormRequest( $form, $wrap ) {
		var $messages = $form.find( '.ff-form__messages' );
		var cfg = getConfig();
		var i18n = cfg.i18n || {};
		var submitUrl = getSubmitUrl( $form );
		var captchaField = getCaptchaFieldName();

		function performSubmit( captchaToken ) {
			var ajaxOptions = buildSubmitRequest( $form, submitUrl, captchaToken || '', captchaField );

			setSubmitting( $form, true );

			$.ajax( ajaxOptions )
				.done( function ( response ) {
					if ( response && response.success ) {
						handleSuccess( $form, $wrap, response );
						return;
					}

					showFormMessage(
						$messages,
						'error',
						( response && response.message ) || i18n.submitFailed || i18n.submitError || 'Submission error.'
					);
				} )
				.fail( function ( xhr ) {
					var data = xhr.responseJSON || {};

					if ( xhr.status === 422 && data.errors ) {
						showFieldErrors( $form, data.errors );
						showFormMessage(
							$messages,
							'error',
							data.message || i18n.validationError || 'Please correct the errors in the form.'
						);
						return;
					}

					showFormMessage(
						$messages,
						'error',
						data.message || i18n.submitError || 'An error occurred while submitting.'
					);
				} )
				.always( function () {
					setSubmitting( $form, false );
				} );
		}

		obtainCaptchaToken( $form )
			.then( function ( token ) {
				performSubmit( token );
			} )
			.catch( function () {
				showFormMessage( $messages, 'error', i18n.antiSpamError || i18n.submitError || 'Anti-spam verification error.' );
			} );
	}

	function handleSuccess( $form, $wrap, data ) {
		var cfg = getConfig();
		var i18n = cfg.i18n || {};
		var $messages = $form.find( '.ff-form__messages' );

		showFormMessage( $messages, 'success', data.message || i18n.success || 'The form has been submitted.' );

		if ( data.extraContent ) {
			$messages.append( $( '<div>', { class: 'ff-form__extra', html: data.extraContent } ) );
		}

		if ( data.hideForm ) {
			$form.find( '.ff-form__body' ).hide();
			$form.find( '.ff-submit' ).hide();
		} else {
			$form[ 0 ].reset();
			resetFileUploadFields( $form );
			updateSubmitLiveState( $form );
		}

		if ( data.redirect && data.redirect.url ) {
			var delay = parseInt( data.redirect.delay, 10 ) || 0;
			setTimeout( function () {
				window.location.href = data.redirect.url;
			}, delay * 1000 );
		}

		$form.trigger( 'ff:submitted', [ data ] );
	}

	function handleSubmit( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $form = $( this );
		var $wrap = $form.closest( '.ff-form-wrap' );
		var $messages = $form.find( '.ff-form__messages' );
		var cfg = getConfig();
		var i18n = cfg.i18n || {};
		var submitUrl = getSubmitUrl( $form );

		$messages.removeClass( 'is-success is-error' ).empty();
		clearFieldErrors( $form );
		syncAllFileUploads( $form );

		if ( ! submitUrl ) {
			showFormMessage( $messages, 'error', i18n.configError || i18n.submitError || 'Form configuration error.' );
			return false;
		}

		var clientErrors = validateClient( $form, $wrap );
		if ( Object.keys( clientErrors ).length ) {
			showFieldErrors( $form, clientErrors );
			var messages = getValidationMessages( $form, $wrap );
			showFormMessage(
				$messages,
				'error',
				messages.validationError || i18n.validationError || 'Please fill in the required fields.'
			);
			return false;
		}

		sendFormRequest( $form, $wrap );

		return false;
	}

	function initStarRatingFields( $context ) {
		( $context || $( document ) ).find( '.ff-star-rating' ).each( function () {
			updateStarRatingDisplay( $( this ), $( this ).find( 'input.ff-input--star-rating' ).val() );
		} );
	}

	function updateStarRatingDisplay( $wrap, value ) {
		var rating = parseInt( value, 10 ) || 0;

		$wrap.find( '.ff-star-rating__star' ).each( function () {
			var starValue = parseInt( $( this ).attr( 'data-value' ), 10 ) || 0;
			$( this ).toggleClass( 'is-active', rating > 0 && starValue <= rating );
		} );
	}

	function updateRangeDisplay( $input ) {
		var outputId = $input.attr( 'data-ff-range-output' );

		if ( ! outputId ) {
			return;
		}

		var $output = $( '#' + $.escapeSelector( outputId ) );
		var $wrap = $input.closest( '.ff-range__track-wrap' );

		if ( ! $output.length || ! $wrap.length ) {
			return;
		}

		var min = parseFloat( $input.attr( 'min' ) );
		var max = parseFloat( $input.attr( 'max' ) );
		var val = parseFloat( $input.val() );
		var thumbWidth = 18;

		if ( isNaN( min ) ) {
			min = 0;
		}

		if ( isNaN( max ) ) {
			max = 100;
		}

		if ( isNaN( val ) ) {
			val = min;
		}

		$output.text( $input.val() );

		var percent = max === min ? 0 : ( val - min ) / ( max - min );
		var trackWidth = $wrap.width();

		if ( trackWidth < 1 ) {
			return;
		}

		var left = percent * ( trackWidth - thumbWidth ) + ( thumbWidth / 2 );
		$output.css( 'left', left + 'px' );
	}

	function initRangeFields( $context ) {
		( $context || $( document ) ).find( 'input[type="range"].ff-input--range' ).each( function () {
			updateRangeDisplay( $( this ) );
		} );
	}

	$( function () {
		initTurnstileWidgets();
		initRangeFields();
		initStarRatingFields();
		initFileUploadFields();
		initSubmitLiveValidation();

		$( document ).on( 'change', 'input.ff-input--file', function () {
			var $input = $( this );
			var $wrap = $input.closest( '.ff-file-upload' );
			var selected = filesFromInput( $input[ 0 ] );

			$input.val( '' );
			applySelectedFiles( $wrap, selected );
		} );

		$( document ).on( 'click', '.ff-file-upload__item-remove', function () {
			var $wrap = $( this ).closest( '.ff-file-upload' );
			var $field = $wrap.closest( '.ff-field' );
			var index = parseInt( $( this ).attr( 'data-file-index' ), 10 );
			var files = getStoredFiles( $wrap );

			if ( isNaN( index ) || index < 0 || index >= files.length ) {
				return;
			}

			files.splice( index, 1 );
			setStoredFiles( $wrap, files );

			var fieldError = validateFileField( $field, getFileFieldMessages( $field ) );

			if ( fieldError ) {
				showSingleFieldError( $field, fieldError );
			} else {
				clearSingleFieldError( $field );
			}

			updateSubmitLiveState( $field.closest( 'form' ) );
		} );

		$( document ).on( 'input change', '.ff-form .ff-field input, .ff-form .ff-field textarea, .ff-form .ff-field select', function () {
			updateSubmitLiveState( $( this ).closest( 'form' ) );
		} );

		$( document ).on( 'input change', 'input[type="range"].ff-input--range', function () {
			updateRangeDisplay( $( this ) );
		} );

		$( window ).on( 'resize', function () {
			$( 'input[type="range"].ff-input--range' ).each( function () {
				updateRangeDisplay( $( this ) );
			} );
		} );

		$( document ).on( 'click', '.ff-star-rating__star', function () {
			var $star = $( this );
			var $wrap = $star.closest( '.ff-star-rating' );
			var $input = $wrap.find( 'input.ff-input--star-rating' );
			var value = String( $star.attr( 'data-value' ) || '' );

			$input.val( value ).trigger( 'change' );
			updateStarRatingDisplay( $wrap, value );
			updateSubmitLiveState( $wrap.closest( 'form' ) );
		} );

		$( document ).on( 'mouseenter', '.ff-star-rating__star', function () {
			var $star = $( this );
			var hoverValue = parseInt( $star.attr( 'data-value' ), 10 ) || 0;

			$star.closest( '.ff-star-rating__stars' ).find( '.ff-star-rating__star' ).each( function () {
				var starValue = parseInt( $( this ).attr( 'data-value' ), 10 ) || 0;
				$( this ).toggleClass( 'is-hover', hoverValue > 0 && starValue <= hoverValue );
			} );
		} );

		$( document ).on( 'mouseleave', '.ff-star-rating__stars', function () {
			$( this ).find( '.ff-star-rating__star' ).removeClass( 'is-hover' );
		} );

		$( document ).on( 'keydown', '.ff-star-rating__star', function ( e ) {
			if ( e.key !== 'Enter' && e.key !== ' ' ) {
				return;
			}

			e.preventDefault();
			$( this ).trigger( 'click' );
		} );

		$( document ).on( 'change', '.ff-choice-group--multiple input[type="checkbox"]', function () {
			var $input = $( this );
			var $field = $input.closest( '.ff-field' );
			var max = parseInt( $field.attr( 'data-ff-max-selections' ), 10 );

			if ( isNaN( max ) || max < 1 || ! $input.is( ':checked' ) ) {
				return;
			}

			var $checked = $field.find( '.ff-choice-group--multiple input[type="checkbox"]:checked' );

			if ( $checked.length > max ) {
				$input.prop( 'checked', false );
			}
		} );

		$( document ).on( 'click', '.ff-open-form', function () {
			openModal( $( this ).attr( 'data-ff-target' ) );
			initTurnstileWidgets();
			initRangeFields( $( '#' + $.escapeSelector( $( this ).attr( 'data-ff-target' ) || '' ) ) );
			initStarRatingFields( $( '#' + $.escapeSelector( $( this ).attr( 'data-ff-target' ) || '' ) ) );
			initFileUploadFields( $( '#' + $.escapeSelector( $( this ).attr( 'data-ff-target' ) || '' ) ) );
			initSubmitLiveValidation( $( '#' + $.escapeSelector( $( this ).attr( 'data-ff-target' ) || '' ) ) );
		} );

		$( document ).on( 'click', '[data-ff-close]', function () {
			closeModal( $( this ).closest( '.ff-form-wrap' ) );
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( '.ff-form-wrap.is-open' ).each( function () {
					closeModal( $( this ) );
				} );
			}
		} );

		$( '.ff-shortcode--trigger' ).each( function () {
			var $wrap = $( this );
			var selector = $wrap.attr( 'data-ff-trigger' );
			var instance = $wrap.attr( 'data-ff-instance' );

			if ( ! selector || ! instance ) {
				return;
			}

			$( document ).on( 'click', selector, function ( ev ) {
				ev.preventDefault();
				openModal( instance );
				initTurnstileWidgets();
			} );
		} );

		$( document ).on( 'submit', '.ff-form', handleSubmit );
		$( document ).on( 'click', '.ff-form .ff-submit', function ( ev ) {
			ev.preventDefault();
			$( this ).closest( 'form' ).trigger( 'submit' );
		} );
	} );
}( jQuery ) );
