/* global fastFormsBuilder, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof fastFormsBuilder === 'undefined' ) {
		return;
	}

	var config = fastFormsBuilder;
	var i18n = config.i18n || {};
	var isDebug = !! config.developerDebug;
	var fieldIcons = {
		text: 'editor-textcolor',
		email: 'email',
		tel: 'phone',
		url: 'admin-links',
		number: 'calculator',
		range: 'leftright',
		star_rating: 'star-filled',
		date: 'calendar-alt',
		textarea: 'editor-paragraph',
		select: 'menu',
		radio: 'marker',
		checkbox: 'yes',
		consent: 'privacy',
		content: 'editor-alignleft',
		file: 'media-default',
		submit: 'arrow-right-alt',
	};

	var Builder = {
		schema: { version: 1, rows: [] },
		selectedFieldId: null,
		selectedRowId: null,
		selectedColumnId: null,
		schemaVersion: 1,
		isSaving: false,
		isReady: false,

		debug: function ( message, context ) {
			if ( ! isDebug ) {
				return;
			}

			if ( window.console && console.log ) {
				console.log( '[Fast Forms]', message, context || '' );
			}

			$.ajax( {
				url: config.restUrl + '/debug',
				method: 'POST',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', config.nonce );
				},
				contentType: 'application/json',
				data: JSON.stringify( {
					message: message,
					context: context || {},
				} ),
			} );
		},

		countFields: function ( schema ) {
			var count = 0;
			( schema.rows || [] ).forEach( function ( row ) {
				( row.columns || [] ).forEach( function ( col ) {
					count += ( col.fields || [] ).length;
				} );
			} );
			return count;
		},

		init: function () {
			var self = this;

			this.setLoading( true );
			this.setSaveStatus( i18n.loadingSchema || 'Loading form…', '' );

			$.ajax( {
				url: config.restUrl + '/forms/' + config.formId,
				method: 'GET',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', config.nonce );
				},
			} )
				.done( function ( response ) {
					self.schemaVersion = response.schemaVersion || 1;
					self.schema = self.normalizeSchema( response.schema || { rows: [] } );
					self.bootstrap();
				} )
				.fail( function ( xhr ) {
					self.debug( 'Schema load failed', {
						formId: config.formId,
						status: xhr.status,
					} );
					self.setSaveStatus( i18n.loadSchemaError || 'Could not load the form.', 'error' );
				} )
				.always( function () {
					self.setLoading( false );
				} );
		},

		setLoading: function ( isLoading ) {
			$( '#ff-form-builder' ).toggleClass( 'is-loading', !! isLoading );
		},

		bootstrap: function () {
			this.isReady = true;
			this.debug( 'Builder init', {
				formId: config.formId,
				fieldCount: this.countFields( this.schema ),
				rowCount: ( this.schema.rows || [] ).length,
				schemaVersion: this.schemaVersion,
			} );
			this.renderPalette();
			this.renderCanvas();
			this.bindEvents();
			this.initTabs();
			this.initRedirectPageSelect();
			this.syncToForm();
			this.updateShortcodePreviews();
			this.updateUploadPathPreview();
			this.updateMergeTagsList();
			this.setSaveStatus( '', '' );
		},

		normalizeSchema: function ( schema ) {
			if ( ! schema || ! schema.rows ) {
				return { version: 1, rows: [] };
			}

			var self = this;
			schema.rows.forEach( function ( row ) {
				if ( typeof row.cssClass !== 'string' ) {
					row.cssClass = '';
				}

				if ( typeof row.htmlId !== 'string' ) {
					row.htmlId = '';
				}

				( row.columns || [] ).forEach( function ( col ) {
					if ( typeof col.cssClass !== 'string' ) {
						col.cssClass = '';
					}

					if ( typeof col.htmlId !== 'string' ) {
						col.htmlId = '';
					}

					( col.fields || [] ).forEach( function ( field ) {
						self.normalizeSubmitField( field );
						self.normalizeConsentField( field );
						self.normalizeContentField( field );
						self.normalizeStarRatingField( field );
						self.normalizeChoiceFieldOptions( field );
					} );
				} );
			} );

			return schema;
		},

		normalizeSubmitField: function ( field ) {
			if ( ! field || field.type !== 'submit' ) {
				return;
			}

			var defaultSubmit = i18n.defaultSubmitText || 'Send';
			var defaultLoading = i18n.defaultLoadingText || 'Sending...';
			var legacySubmit = [
				i18n.submitTextLabel,
				'Submit button text',
				'Submit button text',
			].filter( Boolean );
			var legacyLoading = [
				i18n.loadingTextLabel,
				'Loading text',
				'Loading text',
			].filter( Boolean );

			if ( ! field.submitText || legacySubmit.indexOf( field.submitText ) !== -1 ) {
				field.submitText = defaultSubmit;
			}

			if ( ! field.loadingText || legacyLoading.indexOf( field.loadingText ) !== -1 ) {
				field.loadingText = defaultLoading;
			}

			if ( typeof field.liveValidation === 'undefined' ) {
				field.liveValidation = false;
			}
		},

		sanitizeConsentPreview: function ( html ) {
			var $container = $( '<div>' ).html( html || '' );

			$container.find( 'script, style, iframe, object, embed, link, meta' ).remove();

			return $container.html();
		},

		normalizeContentField: function ( field ) {
			if ( ! field || field.type !== 'content' ) {
				return;
			}

			if ( typeof field.hideLabel === 'undefined' ) {
				field.hideLabel = true;
			}

			field.name = '';
			field.required = false;

			if ( ! field.contentText && field.label ) {
				field.contentText = field.label;
			}
		},

		normalizeConsentField: function ( field ) {
			if ( ! field || field.type !== 'consent' ) {
				return;
			}

			if ( typeof field.hideLabel === 'undefined' ) {
				field.hideLabel = true;
			}

			if ( ! field.consentText && field.label ) {
				field.consentText = field.label;
			}
		},

		normalizeChoiceFieldOptions: function ( field ) {
			if ( ! field || ( field.type !== 'select' && field.type !== 'radio' ) || ! field.options ) {
				return;
			}

			var defaultValues = [];
			var defaultRaw = String( field.defaultValue || '' ).trim();
			var hasSelected = field.options.some( function ( option ) {
				return !! option.selected;
			} );

			if ( ! hasSelected && defaultRaw ) {
				defaultValues = field.allowMultiple
					? defaultRaw.split( ',' ).map( function ( item ) { return item.trim(); } ).filter( Boolean )
					: [ defaultRaw ];
			}

			field.options.forEach( function ( option ) {
				if ( typeof option.selected === 'undefined' ) {
					option.selected = defaultValues.indexOf( String( option.value || '' ) ) !== -1;
				}
			} );
		},

		normalizeStarRatingField: function ( field ) {
			if ( ! field || field.type !== 'star_rating' ) {
				return;
			}

			if ( field.min === '' || field.min === undefined || field.min === null ) {
				field.min = 1;
			}

			if ( field.max === '' || field.max === undefined || field.max === null ) {
				field.max = 5;
			}
		},

		generateId: function ( prefix ) {
			return prefix + '_' + Date.now() + '_' + Math.random().toString( 36 ).slice( 2, 8 );
		},

		getFieldLabel: function ( type ) {
			return ( config.fieldTypes && config.fieldTypes[ type ] ) || type;
		},

		getUsedFieldNames: function () {
			var used = {};

			( this.schema.rows || [] ).forEach( function ( row ) {
				( row.columns || [] ).forEach( function ( col ) {
					( col.fields || [] ).forEach( function ( field ) {
						if ( ! field || field.type === 'submit' || field.type === 'content' || ! field.name ) {
							return;
						}

						used[ field.name ] = true;
					} );
				} );
			} );

			return used;
		},

		getDefaultFieldName: function ( type ) {
			var baseNames = {
				text: 'text',
				email: 'email',
				tel: 'phone',
				textarea: 'textarea',
				select: 'select',
				radio: 'radio',
				checkbox: 'checkbox',
				file: 'file',
				consent: 'consent',
				range: 'range',
				star_rating: 'star_rating',
				url: 'url',
				number: 'number',
				date: 'date',
			};
			var base = baseNames[ type ] || type.replace( /[^a-z0-9_]+/gi, '_' ).replace( /^_+|_+$/g, '' ).toLowerCase() || 'field';
			var used = this.getUsedFieldNames();
			var name = base;
			var index = 2;

			while ( used[ name ] ) {
				name = base + '_' + index;
				index += 1;
			}

			return name;
		},

		getDefaultFieldCssClass: function ( name ) {
			return 'ff-' + String( name ).replace( /_/g, '-' );
		},

		createField: function ( type ) {
			var defaultOptions = [];

			if ( type === 'select' || type === 'radio' ) {
				defaultOptions = [
					{ label: 'Option 1', value: 'option_1' },
					{ label: 'Option 2', value: 'option_2' },
				];
			}

			var name = this.getDefaultFieldName( type );

			if ( type === 'content' ) {
				name = '';
			}

			var cssClass = type === 'content'
				? 'ff-content-block'
				: this.getDefaultFieldCssClass( name );

			return {
				id: this.generateId( 'field' ),
				type: type,
				name: name,
				label: this.getFieldLabel( type ),
				required: false,
				placeholder: '',
				defaultValue: '',
				cssClass: cssClass,
				htmlId: '',
				minLength: '',
				maxLength: '',
				rows: 4,
				min: type === 'star_rating' ? 1 : '',
				max: type === 'star_rating' ? 5 : '',
				step: '',
				options: defaultOptions,
				allowedTypes: '',
				maxFileSize: '',
				submitText: i18n.defaultSubmitText || 'Send',
				loadingText: i18n.defaultLoadingText || 'Sending...',
				liveValidation: false,
				consentText: '',
				contentText: type === 'content'
					? ( i18n.contentDefaultText || 'Enter information text for form visitors.' )
					: '',

				hideLabel: type === 'consent' || type === 'content',
				allowMultiple: false,
				minSelections: '',
				maxSelections: '',
				minFiles: '',
				maxFiles: '',
				fileButtonText: '',
				choiceLayout: 'vertical',
				showUploadHint: false,
			};
		},

		createColumn: function () {
			return {
				id: this.generateId( 'col' ),
				width: 100,
				cssClass: '',
				htmlId: '',
				fields: [],
			};
		},

		createRow: function () {
			return {
				id: this.generateId( 'row' ),
				cssClass: '',
				htmlId: '',
				columns: [ this.createColumn() ],
			};
		},

		recalculateColumnWidths: function ( row ) {
			var count = row.columns.length;
			if ( count < 1 ) {
				return;
			}
			var width = Math.floor( 100 / count );
			row.columns.forEach( function ( col, index ) {
				col.width = index === count - 1 ? 100 - width * ( count - 1 ) : width;
			} );
		},

		findField: function ( fieldId ) {
			var found = null;
			this.schema.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					col.fields.forEach( function ( field ) {
						if ( field.id === fieldId ) {
							found = { field: field, column: col, row: row };
						}
					} );
				} );
			} );
			return found;
		},

		findRow: function ( rowId ) {
			return this.schema.rows.find( function ( r ) {
				return r.id === rowId;
			} );
		},

		findColumn: function ( colId ) {
			var found = null;

			this.schema.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					if ( col.id === colId ) {
						found = { row: row, column: col };
					}
				} );
			} );

			return found;
		},

		bindEvents: function () {
			var self = this;

			$( '#ff-add-row' ).on( 'click', function () {
				self.addRow();
			} );

			$( '#ff-save-form' ).on( 'click', function () {
				self.save();
			} );

			$( '#post' ).on( 'submit', function () {
				if ( ! self.isReady ) {
					return;
				}

				self.syncToForm();
			} );

			$( '#publish, #save-post' ).on( 'click mousedown', function () {
				if ( ! self.isReady ) {
					return;
				}

				self.syncToForm();
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-row', function ( e ) {
				if ( $( e.target ).closest( '.ff-builder-field, .ff-builder-column, .ff-builder-row__actions, .ff-builder-row__reorder, .ff-builder-column__delete' ).length ) {
					return;
				}

				self.selectRow( $( this ).data( 'row-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-column', function ( e ) {
				if ( $( e.target ).closest( '.ff-builder-field, .ff-builder-column__delete' ).length ) {
					return;
				}

				e.stopPropagation();
				self.selectColumn( $( this ).data( 'col-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-field', function ( e ) {
				if ( $( e.target ).closest( '.ff-builder-field__delete' ).length ) {
					return;
				}
				e.stopPropagation();
				self.selectField( $( this ).data( 'field-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-field__delete', function ( e ) {
				e.stopPropagation();
				self.deleteField( $( this ).data( 'field-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-row__add-col', function () {
				self.addColumn( $( this ).data( 'row-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-row__move-up', function ( e ) {
				e.preventDefault();
				self.moveRow( $( this ).data( 'row-id' ), -1 );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-row__move-down', function ( e ) {
				e.preventDefault();
				self.moveRow( $( this ).data( 'row-id' ), 1 );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-row__delete', function () {
				self.deleteRow( $( this ).data( 'row-id' ) );
			} );

			$( '#ff-builder-canvas' ).on( 'click', '.ff-builder-column__delete', function () {
				self.deleteColumn( $( this ).data( 'row-id' ), $( this ).data( 'col-id' ) );
			} );

			$( '#ff-field-settings' ).on( 'input change', '[data-setting]', function () {
				self.updateFieldSetting( $( this ) );
			} );

			$( '#ff-field-settings' ).on( 'input change', '[data-row-setting]', function () {
				self.updateRowSetting( $( this ) );
			} );

			$( '#ff-field-settings' ).on( 'input change', '[data-col-setting]', function () {
				self.updateColumnSetting( $( this ) );
			} );

			$( '#ff-field-settings' ).on( 'input change', '.ff-options-editor [data-option-part]', function () {
				self.syncOptionsFromEditor();
			} );

			$( '#ff-field-settings' ).on( 'change', '.ff-options-editor__default', function () {
				var found = self.findField( self.selectedFieldId );
				var $row = $( this ).closest( '.ff-options-editor__row' );
				var allowMultiple = found && found.field.type === 'radio' && found.field.allowMultiple;

				if ( $( this ).is( ':checked' ) && ! allowMultiple ) {
					$row.siblings().find( '.ff-options-editor__default' ).prop( 'checked', false );
				}

				self.syncOptionsFromEditor();
			} );

			$( '#ff-field-settings' ).on( 'click', '.ff-options-editor__add', function () {
				self.addOptionRow();
			} );

			$( '#ff-field-settings' ).on( 'click', '.ff-options-editor__remove', function () {
				self.removeOptionRow( $( this ) );
			} );

			$( '#ff-form-builder' ).on( 'input change', '[data-ff-group="form"][data-ff-setting]', function () {
				self.updateShortcodePreviews();
				self.updateUploadPathPreview();
			} );

			$( '#title' ).on( 'input', function () {
				self.updateUploadPathPreview();
			} );

			$( document ).on( 'click', '.ff-shortcode-copy', function () {
				self.copyShortcode( $( this ) );
			} );
		},

		initTabs: function () {
			var self = this;

			$( '#ff-builder-tabs' ).tabs( {
				heightStyle: 'content',
				activate: function ( event, ui ) {
					if ( ui.newPanel && ui.newPanel.attr( 'id' ) === 'ff-tab-notifications' ) {
						self.initRedirectPageSelect();
					}
				},
			} );
		},

		initRedirectPageSelect: function () {
			var $select = $( '#ff-notifications-redirectPageId' );

			if ( ! $select.length || typeof $.fn.select2 !== 'function' ) {
				return;
			}

			if ( $select.hasClass( 'select2-hidden-accessible' ) ) {
				return;
			}

			$select.select2( {
				allowClear: true,
				placeholder: i18n.selectPage || '— Select page —',
				width: '100%',
				ajax: {
					url: config.pagesRestUrl,
					dataType: 'json',
					delay: 250,
					cache: true,
					headers: {
						'X-WP-Nonce': config.nonce,
					},
					data: function ( params ) {
						return {
							search: params.term || '',
							per_page: 20,
							status: 'publish',
							_fields: 'id,title',
						};
					},
					processResults: function ( data ) {
						var pages = Array.isArray( data ) ? data : [];

						return {
							results: pages.map( function ( page ) {
								var title = page.title && page.title.rendered ? page.title.rendered : page.title;

								return {
									id: page.id,
									text: title || ( '#' + page.id ),
								};
							} ),
						};
					},
				},
				language: {
					inputTooShort: function () {
						return i18n.searchPages || 'Search pages…';
					},
					searching: function () {
						return i18n.searchPages || 'Search pages…';
					},
				},
				minimumInputLength: 0,
			} );
		},

		renderPalette: function () {
			var $palette = $( '#ff-field-palette' );
			$palette.empty();

			Object.keys( config.fieldTypes || {} ).forEach( function ( type ) {
				var icon = fieldIcons[ type ] || 'admin-generic';
				var $item = $( '<li>', {
					class: 'ff-field-palette__item',
					'data-field-type': type,
					text: config.fieldTypes[ type ],
				} );
				$item.prepend( $( '<span>', { class: 'dashicons dashicons-' + icon } ) );
				$palette.append( $item );
			} );

			this.initPaletteDraggable();
		},

		initPaletteDraggable: function () {
			$( '.ff-field-palette__item' ).draggable( {
				helper: 'clone',
				revert: 'invalid',
				appendTo: 'body',
				zIndex: 10000,
			} );
		},

		renderCanvas: function () {
			var self = this;
			var $canvas = $( '#ff-builder-canvas' );
			$canvas.empty();

			if ( ! this.schema.rows.length ) {
				$canvas.append(
					$( '<div>', { class: 'ff-builder-canvas__empty', text: i18n.emptyCanvas } )
				);
				this.updateMergeTagsList();
				return;
			}

			var $rows = $( '<ul>', { class: 'ff-builder-rows' } );

			this.schema.rows.forEach( function ( row, rowIndex ) {
				$rows.append( self.renderRow( row, rowIndex ) );
			} );

			$canvas.append( $rows );
			this.initSortable();
			this.initDroppable();
			this.highlightSelected();
			this.updateMergeTagsList();
		},

		renderRow: function ( row, rowIndex ) {
			var self = this;
			var rowCount = this.schema.rows.length;
			var $row = $( '<li>', { class: 'ff-builder-row', 'data-row-id': row.id } );

			if ( row.id === this.selectedRowId ) {
				$row.addClass( 'is-selected' );
			}

			var $header = $( '<div>', { class: 'ff-builder-row__header' } );
			var $title = $( '<div>', { class: 'ff-builder-row__title' } );
			var rowLabel = ( i18n.rowLabelPrefix || 'Row' ) + ' ' + ( rowIndex + 1 );
			if ( row.cssClass ) {
				rowLabel += ' · .' + row.cssClass;
			}
			if ( row.htmlId ) {
				rowLabel += ' · #' + row.htmlId;
			}
			$title.append(
				$( '<span>', {
					class: 'ff-builder-row__label',
					text: rowLabel,
				} )
			);

			var $reorder = $( '<div>', { class: 'ff-builder-row__reorder' } );
			$reorder.append(
				this.createIconButton( {
					className: 'ff-builder-row__move-up',
					icon: 'arrow-up-alt2',
					label: i18n.moveRowUp || 'Move row up',
					disabled: 0 === rowIndex,
					attributes: {
						'data-row-id': row.id,
					},
				} ),
				this.createIconButton( {
					className: 'ff-builder-row__move-down',
					icon: 'arrow-down-alt2',
					label: i18n.moveRowDown || 'Move row down',
					disabled: rowIndex >= rowCount - 1,
					attributes: {
						'data-row-id': row.id,
					},
				} )
			);
			$title.append( $reorder );
			$header.append( $title );

			var $actions = $( '<div>', { class: 'ff-builder-row__actions' } );
			$actions.append(
				$( '<a>', {
					class: 'button-link ff-builder-row__add-col',
					'data-row-id': row.id,
					text: i18n.addColumn,
				} )
			);
			$actions.append(
				this.createTrashButton( {
					className: 'ff-builder-row__delete',
					label: i18n.deleteRow,
					attributes: {
						'data-row-id': row.id,
					},
				} )
			);
			$header.append( $actions );
			$row.append( $header );

			var $columns = $( '<ul>', { class: 'ff-builder-columns' } );
			row.columns.forEach( function ( col ) {
				$columns.append( self.renderColumn( row, col ) );
			} );
			$row.append( $columns );

			return $row;
		},

		renderColumn: function ( row, col ) {
			var self = this;
			var $col = $( '<li>', {
				class: 'ff-builder-column',
				'data-row-id': row.id,
				'data-col-id': col.id,
				css: { flex: col.width + ' 1 0' },
			} );

			if ( col.id === this.selectedColumnId ) {
				$col.addClass( 'is-selected' );
			}

			var $header = $( '<div>', { class: 'ff-builder-column__header' } );
			var colIndex = row.columns.findIndex( function ( column ) {
				return column.id === col.id;
			} );
			var colLabel = ( i18n.columnLabelPrefix || 'Column' ) + ' ' + ( colIndex + 1 );

			if ( col.cssClass ) {
				colLabel += ' · .' + col.cssClass;
			}

			if ( col.htmlId ) {
				colLabel += ' · #' + col.htmlId;
			}

			$header.append(
				$( '<span>', {
					class: 'ff-builder-column__label',
					text: colLabel,
				} )
			);

			if ( row.columns.length > 1 ) {
				$header.append(
					this.createTrashButton( {
						className: 'ff-builder-column__delete',
						label: i18n.deleteColumn,
						attributes: {
							'data-row-id': row.id,
							'data-col-id': col.id,
						},
					} )
				);
			}
			$col.append( $header );

			var $fields = $( '<ul>', {
				class: 'ff-builder-fields',
				'data-row-id': row.id,
				'data-col-id': col.id,
			} );

			if ( ! col.fields.length ) {
				$fields.append(
					$( '<li>', { class: 'ff-builder-fields__placeholder', text: i18n.dragFieldHere } )
				);
			} else {
				col.fields.forEach( function ( field ) {
					$fields.append( self.renderField( field ) );
				} );
			}

			$col.append( $fields );
			return $col;
		},

		renderField: function ( field ) {
			var required = field.required ? ' *' : '';
			var $field = $( '<li>', {
				class: 'ff-builder-field',
				'data-field-id': field.id,
				'data-field-type': field.type,
			} );

			if ( field.id === this.selectedFieldId ) {
				$field.addClass( 'is-selected' );
			}

			var previewLabel = field.type === 'consent'
				? ( field.consentText || field.label || field.type )
				: ( field.type === 'content'
					? ( field.contentText || field.label || field.type )
					: ( field.label || field.type ) );
			var meta = this.getFieldLabel( field.type ) + ( field.name ? ' · ' + field.name : '' );

			if ( field.type === 'select' || field.type === 'radio' ) {
				var optionCount = ( field.options || [] ).filter( function ( opt ) {
					return opt && ( opt.label || opt.value );
				} ).length;
				meta += ' · ' + optionCount + ' ' + ( i18n.options || 'options' ).toLowerCase();

				if ( field.type === 'radio' && field.allowMultiple ) {
					meta += ' · ' + ( i18n.allowMultiple || 'multiple' ).toLowerCase();
				}
			}

			if ( field.htmlId ) {
				meta += ' · #' + field.htmlId;
			}

			var $info = $( '<div>', { class: 'ff-builder-field__info' } );
			var $previewLabel = $( '<span>', { class: 'ff-builder-field__label' } );

			if ( field.type === 'consent' || field.type === 'content' ) {
				$previewLabel.html( this.sanitizeConsentPreview( previewLabel || field.type ) + required );
			} else {
				$previewLabel.text( previewLabel + required );
			}

			$info.append( $previewLabel );
			$info.append(
				$( '<span>', {
					class: 'ff-builder-field__meta',
					text: meta,
				} )
			);

			var $actions = $( '<div>', { class: 'ff-builder-field__actions' } );
			$actions.append(
				this.createTrashButton( {
					className: 'ff-builder-field__delete',
					label: i18n.deleteField,
					attributes: {
						'data-field-id': field.id,
					},
				} )
			);

			$field.append( $info, $actions );
			return $field;
		},

		initSortable: function () {
			var self = this;

			$( '.ff-builder-rows' ).sortable( {
				items: '> .ff-builder-row',
				handle: '.ff-builder-row__label',
				placeholder: 'ff-builder-row-placeholder',
				tolerance: 'pointer',
				update: function () {
					self.syncRowsOrder();
				},
			} );

			$( '.ff-builder-fields' ).sortable( {
				items: '> .ff-builder-field',
				connectWith: '.ff-builder-fields',
				placeholder: 'ff-builder-field-placeholder',
				tolerance: 'pointer',
				update: function () {
					self.syncFieldsOrder();
				},
			} );
		},

		initDroppable: function () {
			var self = this;

			$( '.ff-builder-column' ).droppable( {
				accept: '.ff-field-palette__item',
				hoverClass: 'ui-droppable-hover',
				drop: function ( event, ui ) {
					var type = ui.draggable.data( 'field-type' );
					var rowId = $( this ).data( 'row-id' );
					var colId = $( this ).data( 'col-id' );
					self.addFieldToColumn( rowId, colId, type );
				},
			} );
		},

		addFieldToColumn: function ( rowId, colId, type ) {
			var row = this.findRow( rowId );
			if ( ! row ) {
				return;
			}
			var col = row.columns.find( function ( c ) {
				return c.id === colId;
			} );
			if ( ! col ) {
				return;
			}
			var field = this.createField( type );
			col.fields.push( field );
			this.debug( 'Field added', {
				formId: config.formId,
				fieldId: field.id,
				fieldType: type,
				fieldCount: this.countFields( this.schema ),
			} );
			this.renderCanvas();
			this.selectField( field.id );
		},

		syncRowsOrder: function () {
			var newRows = [];
			$( '.ff-builder-row' ).each( function () {
				var rowId = $( this ).data( 'row-id' );
				var row = Builder.findRow( rowId );
				if ( row ) {
					newRows.push( row );
				}
			} );
			this.schema.rows = newRows;
		},

		syncFieldsOrder: function () {
			var fieldById = {};

			this.schema.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					col.fields.forEach( function ( field ) {
						fieldById[ field.id ] = field;
					} );
				} );
			} );

			this.schema.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					var newFields = [];

					$( '.ff-builder-fields[data-col-id="' + col.id + '"] > .ff-builder-field' ).each( function () {
						var fieldId = $( this ).data( 'field-id' );
						if ( fieldById[ fieldId ] ) {
							newFields.push( fieldById[ fieldId ] );
						}
					} );
					col.fields = newFields;

					var $list = $( '.ff-builder-fields[data-col-id="' + col.id + '"]' );
					var hasFieldsInDom = $list.find( '> .ff-builder-field' ).length > 0;

					if ( ! col.fields.length && ! hasFieldsInDom && ! $list.find( '.ff-builder-fields__placeholder' ).length ) {
						$list.append(
							$( '<li>', { class: 'ff-builder-fields__placeholder', text: i18n.dragFieldHere } )
						);
					} else if ( col.fields.length || hasFieldsInDom ) {
						$list.find( '.ff-builder-fields__placeholder' ).remove();
					}
				} );
			} );

			this.renderSettingsPanel();
		},

		addRow: function () {
			this.schema.rows.push( this.createRow() );
			this.renderCanvas();
		},

		addColumn: function ( rowId ) {
			var row = this.findRow( rowId );
			if ( ! row ) {
				return;
			}
			row.columns.push( this.createColumn() );
			this.recalculateColumnWidths( row );
			this.renderCanvas();
		},

		deleteRow: function ( rowId ) {
			if ( ! window.confirm( i18n.confirmDeleteRow ) ) {
				return;
			}
			this.schema.rows = this.schema.rows.filter( function ( r ) {
				return r.id !== rowId;
			} );
			this.selectedFieldId = null;
			if ( this.selectedRowId === rowId ) {
				this.selectedRowId = null;
			}
			if ( this.selectedColumnId && ! this.findColumn( this.selectedColumnId ) ) {
				this.selectedColumnId = null;
			}
			this.renderCanvas();
			this.renderSettingsPanel();
		},

		moveRow: function ( rowId, direction ) {
			var rows = this.schema.rows;
			var index = rows.findIndex( function ( row ) {
				return row.id === rowId;
			} );

			if ( -1 === index ) {
				return;
			}

			var newIndex = index + direction;

			if ( newIndex < 0 || newIndex >= rows.length ) {
				return;
			}

			var moved = rows.splice( index, 1 )[ 0 ];
			rows.splice( newIndex, 0, moved );
			this.renderCanvas();
		},

		deleteColumn: function ( rowId, colId ) {
			if ( ! window.confirm( i18n.confirmDeleteCol ) ) {
				return;
			}
			var row = this.findRow( rowId );
			if ( ! row || row.columns.length <= 1 ) {
				return;
			}
			row.columns = row.columns.filter( function ( c ) {
				return c.id !== colId;
			} );
			this.recalculateColumnWidths( row );
			this.selectedFieldId = null;
			if ( this.selectedColumnId === colId ) {
				this.selectedColumnId = null;
			}
			this.renderCanvas();
			this.renderSettingsPanel();
		},

		deleteField: function ( fieldId ) {
			if ( ! window.confirm( i18n.confirmDeleteFld ) ) {
				return;
			}
			this.schema.rows.forEach( function ( row ) {
				row.columns.forEach( function ( col ) {
					col.fields = col.fields.filter( function ( f ) {
						return f.id !== fieldId;
					} );
				} );
			} );
			if ( this.selectedFieldId === fieldId ) {
				this.selectedFieldId = null;
			}
			this.renderCanvas();
			this.renderSettingsPanel();
		},

		selectField: function ( fieldId ) {
			this.selectedFieldId = fieldId;
			this.selectedRowId = null;
			this.selectedColumnId = null;
			this.highlightSelected();
			this.renderSettingsPanel();
		},

		selectRow: function ( rowId ) {
			this.selectedRowId = rowId;
			this.selectedFieldId = null;
			this.selectedColumnId = null;
			this.highlightSelected();
			this.renderSettingsPanel();
		},

		selectColumn: function ( colId ) {
			this.selectedColumnId = colId;
			this.selectedFieldId = null;
			this.selectedRowId = null;
			this.highlightSelected();
			this.renderSettingsPanel();
		},

		highlightSelected: function () {
			$( '.ff-builder-field' ).removeClass( 'is-selected' );
			$( '.ff-builder-row' ).removeClass( 'is-selected' );
			$( '.ff-builder-column' ).removeClass( 'is-selected' );

			if ( this.selectedFieldId ) {
				$( '.ff-builder-field[data-field-id="' + this.selectedFieldId + '"]' ).addClass( 'is-selected' );
			} else if ( this.selectedColumnId ) {
				$( '.ff-builder-column[data-col-id="' + this.selectedColumnId + '"]' ).addClass( 'is-selected' );
			} else if ( this.selectedRowId ) {
				$( '.ff-builder-row[data-row-id="' + this.selectedRowId + '"]' ).addClass( 'is-selected' );
			}
		},

		renderSettingsPanel: function () {
			var $panel = $( '#ff-field-settings' );
			$panel.empty();

			if ( this.selectedFieldId ) {
				var found = this.findField( this.selectedFieldId );

				if ( ! found ) {
					return;
				}

				this.renderFieldSettingsPanel( $panel, found.field );
				return;
			}

			if ( this.selectedColumnId ) {
				this.renderColumnSettingsPanel( $panel );
				return;
			}

			if ( this.selectedRowId ) {
				this.renderRowSettingsPanel( $panel );
				return;
			}

			$panel.append(
				$( '<p>', { class: 'ff-field-settings__empty', text: i18n.noSelection || i18n.noFieldSelected } )
			);
		},

		renderFieldSettingsPanel: function ( $panel, field ) {
			var $form = $( '<div>', { class: 'ff-field-settings__form' } );

			if ( field.type === 'content' ) {
				$form.append( this.settingsTextarea( 'contentText', i18n.contentTextLabel, field.contentText || field.label || '', this.getTooltip( 'contentText' ), i18n.consentHtmlHint ) );
				$form.append( this.settingsInput( 'label', i18n.contentOptionalLabel || i18n.label, field.label, 'text', this.getTooltip( 'contentOptionalLabel' ) || this.getTooltip( 'label' ) ) );
				$form.append( this.settingsCheckbox( 'hideLabel', i18n.hideLabel, field.hideLabel !== false, this.getTooltip( 'hideLabel' ) ) );
				$form.append( this.settingsInput( 'cssClass', i18n.cssClass, field.cssClass, 'text', this.getTooltip( 'cssClass' ) ) );
				$form.append( this.settingsInput( 'htmlId', i18n.htmlId, field.htmlId || '', 'text', this.getTooltip( 'htmlId' ) ) );
				$panel.append( $form );
				return;
			}

			if ( field.type === 'consent' ) {
				$form.append( this.settingsTextarea( 'consentText', i18n.consentTextLabel, field.consentText || field.label || '', this.getTooltip( 'consentText' ), i18n.consentHtmlHint ) );
			}

			$form.append( this.settingsInput( 'label', i18n.label, field.label, 'text', this.getTooltip( 'label' ) ) );
			$form.append( this.settingsInput( 'name', i18n.name, field.name, 'text', this.getTooltip( 'name' ) ) );
			$form.append( this.settingsCheckbox( 'required', i18n.required, field.required, this.getTooltip( 'required' ) ) );

			if ( field.type !== 'consent' && field.type !== 'select' && field.type !== 'radio' ) {
				$form.append( this.settingsInput( 'placeholder', i18n.placeholder, field.placeholder, 'text', this.getTooltip( 'placeholder' ) ) );
				$form.append( this.settingsInput( 'defaultValue', i18n.defaultValue, field.defaultValue, 'text', this.getTooltip( 'defaultValue' ) ) );
			} else if ( field.type !== 'consent' ) {
				$form.append( this.settingsInput( 'placeholder', i18n.placeholder, field.placeholder, 'text', this.getTooltip( 'placeholder' ) ) );
			}

			$form.append( this.settingsInput( 'cssClass', i18n.cssClass, field.cssClass, 'text', this.getTooltip( 'cssClass' ) ) );
			$form.append( this.settingsInput( 'htmlId', i18n.htmlId, field.htmlId || '', 'text', this.getTooltip( 'htmlId' ) ) );

			if ( [ 'text', 'email', 'tel', 'url', 'textarea' ].indexOf( field.type ) !== -1 ) {
				$form.append( this.settingsInput( 'minLength', i18n.minLength, field.minLength, 'number', this.getTooltip( 'minLength' ) ) );
				$form.append( this.settingsInput( 'maxLength', i18n.maxLength, field.maxLength, 'number', this.getTooltip( 'maxLength' ) ) );
			}

			if ( field.type === 'textarea' ) {
				$form.append( this.settingsInput( 'rows', i18n.rows, field.rows, 'number', this.getTooltip( 'rows' ) ) );
			}

			if ( [ 'number', 'range' ].indexOf( field.type ) !== -1 ) {
				$form.append( this.settingsInput( 'min', i18n.min, field.min, 'number', this.getTooltip( 'min' ) ) );
				$form.append( this.settingsInput( 'max', i18n.max, field.max, 'number', this.getTooltip( 'max' ) ) );
				$form.append( this.settingsInput( 'step', i18n.step, field.step, 'text', this.getTooltip( 'step' ) ) );
			}

			if ( field.type === 'star_rating' ) {
				$form.append( this.settingsInput( 'min', i18n.minStars, field.min, 'number', this.getTooltip( 'starMin' ) ) );
				$form.append( this.settingsInput( 'max', i18n.maxStars, field.max, 'number', this.getTooltip( 'starMax' ) ) );
			}

			if ( field.type === 'radio' ) {
				$form.append( this.settingsSelect( 'choiceLayout', i18n.choiceLayout, field.choiceLayout || 'vertical', [
					{ value: 'vertical', label: i18n.choiceLayoutVertical || 'Vertical' },
					{ value: 'horizontal', label: i18n.choiceLayoutHorizontal || 'Horizontal' },
				], this.getTooltip( 'choiceLayout' ) ) );
				$form.append( this.settingsCheckbox( 'allowMultiple', i18n.allowMultiple, !! field.allowMultiple, this.getTooltip( 'allowMultiple' ) ) );

				if ( field.allowMultiple ) {
					$form.append( this.settingsInput( 'minSelections', i18n.minSelections, field.minSelections, 'number', this.getTooltip( 'minSelections' ) ) );
					$form.append( this.settingsInput( 'maxSelections', i18n.maxSelections, field.maxSelections, 'number', this.getTooltip( 'maxSelections' ) ) );
				}
			}

			if ( field.type === 'select' || field.type === 'radio' ) {
				$form.append( this.settingsOptionsEditor( field ) );
			}

			if ( field.type === 'select' ) {
				$form.append( this.settingsSelect( 'choiceLayout', i18n.choiceLayout, field.choiceLayout || 'vertical', [
					{ value: 'vertical', label: i18n.choiceLayoutVertical || 'Vertical' },
					{ value: 'horizontal', label: i18n.choiceLayoutHorizontal || 'Horizontal' },
				], this.getTooltip( 'choiceLayoutSelect' ) || this.getTooltip( 'choiceLayout' ) ) );
			}

			if ( field.type === 'file' ) {
				$form.append( this.settingsSelect( 'choiceLayout', i18n.choiceLayout, field.choiceLayout || 'vertical', [
					{ value: 'vertical', label: i18n.choiceLayoutVertical || 'Vertical' },
					{ value: 'horizontal', label: i18n.choiceLayoutHorizontal || 'Horizontal' },
				], this.getTooltip( 'choiceLayout' ) ) );
				$form.append( this.settingsInput( 'allowedTypes', i18n.allowedTypes, field.allowedTypes, 'text', this.getTooltip( 'allowedTypes' ) ) );
				$form.append( this.settingsInput( 'maxFileSize', i18n.maxFileSize, field.maxFileSize, 'number', this.getTooltip( 'maxFileSize' ) ) );
				$form.append( this.settingsCheckbox( 'showUploadHint', i18n.showUploadHint, !! field.showUploadHint, this.getTooltip( 'showUploadHint' ) ) );
				$form.append( this.settingsInput(
					'fileButtonText',
					i18n.fileButtonText,
					field.fileButtonText || '',
					'text',
					this.getTooltip( 'fileButtonText' ),
					field.allowMultiple
						? ( i18n.defaultFilesButtonText || 'Choose files' )
						: ( i18n.defaultFileButtonText || 'Choose file' )
				) );
				$form.append( this.settingsCheckbox( 'allowMultiple', i18n.allowMultipleFiles, !! field.allowMultiple, this.getTooltip( 'allowMultipleFiles' ) ) );

				if ( field.allowMultiple ) {
					$form.append( this.settingsInput( 'minFiles', i18n.minFiles, field.minFiles, 'number', this.getTooltip( 'minFiles' ) ) );
					$form.append( this.settingsInput( 'maxFiles', i18n.maxFiles, field.maxFiles, 'number', this.getTooltip( 'maxFiles' ) ) );
				}
			}

			if ( field.type === 'consent' ) {
				$form.append( this.settingsCheckbox( 'hideLabel', i18n.hideLabel, field.hideLabel !== false, this.getTooltip( 'hideLabel' ) ) );
			}

			if ( field.type === 'submit' ) {
				$form.append( this.settingsInput( 'submitText', i18n.submitTextLabel, field.submitText || i18n.defaultSubmitText, 'text', this.getTooltip( 'submitText' ) ) );
				$form.append( this.settingsInput( 'loadingText', i18n.loadingTextLabel, field.loadingText || i18n.defaultLoadingText, 'text', this.getTooltip( 'loadingText' ) ) );
				$form.append( this.settingsCheckbox( 'liveValidation', i18n.liveValidationLabel, !! field.liveValidation, this.getTooltip( 'liveValidation' ) ) );
			}

			$panel.append( $form );
			this.initOptionsEditorSortable();
		},

		renderRowSettingsPanel: function ( $panel ) {
			var row = this.findRow( this.selectedRowId );

			if ( ! row ) {
				return;
			}

			var $form = $( '<div>', { class: 'ff-field-settings__form' } );
			$form.append(
				$( '<p>', {
					class: 'ff-field-settings__intro',
					text: i18n.rowSettingsIntro || i18n.rowLabelPrefix || 'Row',
				} )
			);
			$form.append( this.settingsRowInput( 'cssClass', i18n.cssClass, row.cssClass || '', 'text', this.getTooltip( 'rowCssClass' ) || this.getTooltip( 'cssClass' ) ) );
			$form.append( this.settingsRowInput( 'htmlId', i18n.htmlId, row.htmlId || '', 'text', this.getTooltip( 'rowHtmlId' ) || this.getTooltip( 'htmlId' ) ) );
			$panel.append( $form );
		},

		settingsRowInput: function ( key, label, value, type, tooltip, placeholder ) {
			var $group = this.settingsInput( key, label, value, type, tooltip, placeholder );
			$group.find( '[data-setting]' ).removeAttr( 'data-setting' ).attr( 'data-row-setting', key );
			return $group;
		},

		updateRowSetting: function ( $input ) {
			if ( ! this.selectedRowId ) {
				return;
			}

			var row = this.findRow( this.selectedRowId );

			if ( ! row ) {
				return;
			}

			var key = $input.data( 'row-setting' );
			row[ key ] = $input.val();
			this.renderCanvas();
			this.highlightSelected();
			this.renderSettingsPanel();
		},

		renderColumnSettingsPanel: function ( $panel ) {
			var found = this.findColumn( this.selectedColumnId );

			if ( ! found ) {
				return;
			}

			var column = found.column;
			var $form = $( '<div>', { class: 'ff-field-settings__form' } );

			$form.append(
				$( '<p>', {
					class: 'ff-field-settings__intro',
					text: i18n.columnSettingsIntro || i18n.columnLabelPrefix || 'Column',
				} )
			);
			$form.append( this.settingsColInput( 'cssClass', i18n.cssClass, column.cssClass || '', 'text', this.getTooltip( 'columnCssClass' ) || this.getTooltip( 'cssClass' ) ) );
			$form.append( this.settingsColInput( 'htmlId', i18n.htmlId, column.htmlId || '', 'text', this.getTooltip( 'columnHtmlId' ) || this.getTooltip( 'htmlId' ) ) );
			$panel.append( $form );
		},

		settingsColInput: function ( key, label, value, type, tooltip, placeholder ) {
			var $group = this.settingsInput( key, label, value, type, tooltip, placeholder );
			$group.find( '[data-setting]' ).removeAttr( 'data-setting' ).attr( 'data-col-setting', key );
			return $group;
		},

		updateColumnSetting: function ( $input ) {
			if ( ! this.selectedColumnId ) {
				return;
			}

			var found = this.findColumn( this.selectedColumnId );

			if ( ! found ) {
				return;
			}

			var key = $input.data( 'col-setting' );
			found.column[ key ] = $input.val();
			this.renderCanvas();
			this.highlightSelected();
			this.renderSettingsPanel();
		},

		settingsOptionsEditor: function ( field ) {
			var self = this;
			var $group = $( '<div>', { class: 'ff-field-settings__group ff-options-editor' } );
			var $label = $( '<label>' );
			$label.append( $( '<span>', { class: 'ff-field-settings__label-text', text: i18n.options || 'Options' } ) );

			var tooltip = this.getTooltip( 'options' );
			if ( tooltip ) {
				$label.append(
					$( '<span>', {
						class: 'ff-field-settings__help dashicons dashicons-info',
						'data-tooltip': tooltip,
						'aria-label': tooltip,
						tabindex: '0',
						role: 'button',
					} )
				);
			}

			$group.append( $label );

			var $list = $( '<div>', { class: 'ff-options-editor__list' } );
			var options = field.options && field.options.length ? field.options : [ { label: '', value: '' } ];

			options.forEach( function ( option, index ) {
				$list.append( self.renderOptionRow( option, index, field ) );
			} );

			$group.append( $list );
			$group.append(
				$( '<button>', {
					type: 'button',
					class: 'button button-secondary ff-options-editor__add',
					text: i18n.addOption || 'Add option',
				} )
			);

			return $group;
		},

		renderOptionRow: function ( option, index, field ) {
			var $row = $( '<div>', { class: 'ff-options-editor__row', 'data-option-index': index } );
			$row.append(
				$( '<button>', {
					type: 'button',
					class: 'ff-options-editor__handle dashicons dashicons-menu',
					'aria-label': i18n.dragOption || 'Drag to reorder',
					title: i18n.dragOption || 'Drag to reorder',
				} )
			);

			var $fields = $( '<div>', { class: 'ff-options-editor__fields' } );
			$fields.append(
				$( '<input>', {
					type: 'text',
					class: 'ff-options-editor__label',
					'data-option-part': 'label',
					placeholder: i18n.optionLabel || 'Option label',
					value: option.label || '',
				} )
			);
			$fields.append(
				$( '<input>', {
					type: 'text',
					class: 'ff-options-editor__value',
					'data-option-part': 'value',
					placeholder: i18n.optionValue || 'Option value',
					value: option.value || '',
				} )
			);
			$row.append( $fields );

			var $defaultWrap = $( '<label>', { class: 'ff-options-editor__default-wrap', title: this.getTooltip( 'optionDefault' ) || '' } );
			$defaultWrap.append(
				$( '<input>', {
					type: field && field.type === 'radio' && field.allowMultiple ? 'checkbox' : 'checkbox',
					class: 'ff-options-editor__default',
					'data-option-part': 'selected',
					checked: !! option.selected,
				} )
			);
			$defaultWrap.append(
				$( '<span>', { class: 'ff-options-editor__default-label', text: i18n.optionDefault || 'Default' } )
			);
			$row.append( $defaultWrap );

			$row.append(
				this.createTrashButton( {
					className: 'ff-options-editor__remove',
					label: i18n.removeOption || 'Remove option',
				} )
			);

			return $row;
		},

		initOptionsEditorSortable: function () {
			var self = this;
			var $list = $( '#ff-field-settings .ff-options-editor__list' );

			if ( ! $list.length ) {
				return;
			}

			if ( $list.data( 'ui-sortable' ) ) {
				$list.sortable( 'destroy' );
			}

			$list.sortable( {
				handle: '.ff-options-editor__handle',
				axis: 'y',
				containment: 'parent',
				tolerance: 'pointer',
				update: function () {
					self.syncOptionsFromEditor();
				},
			} );
		},

		syncOptionsFromEditor: function () {
			if ( ! this.selectedFieldId ) {
				return;
			}

			var found = this.findField( this.selectedFieldId );
			if ( ! found ) {
				return;
			}

			var options = [];

			$( '#ff-field-settings .ff-options-editor__row' ).each( function () {
				var label = $( this ).find( '[data-option-part="label"]' ).val();
				var value = $( this ).find( '[data-option-part="value"]' ).val();
				var selected = $( this ).find( '.ff-options-editor__default' ).is( ':checked' );

				label = String( label || '' ).trim();
				value = String( value || '' ).trim();

				if ( ! label && ! value ) {
					return;
				}

				options.push( {
					label: label || value,
					value: value || label,
					selected: selected,
				} );
			} );

			found.field.options = options;
			this.syncDefaultValueFromOptions( found.field );
			this.updateFieldPreview( found.field );
			this.updateMergeTagsList();
		},

		syncDefaultValueFromOptions: function ( field ) {
			if ( ! field || ! field.options ) {
				return;
			}

			var selected = field.options
				.filter( function ( option ) {
					return !! option.selected;
				} )
				.map( function ( option ) {
					return String( option.value || '' );
				} )
				.filter( Boolean );

			if ( field.type === 'radio' && field.allowMultiple ) {
				field.defaultValue = selected.join( ',' );
				return;
			}

			field.defaultValue = selected[0] || '';

			if ( selected.length > 1 ) {
				var keep = selected[ 0 ];
				field.options.forEach( function ( option ) {
					option.selected = String( option.value || '' ) === keep;
				} );
			}
		},

		addOptionRow: function () {
			var $list = $( '#ff-field-settings .ff-options-editor__list' );

			if ( ! $list.length ) {
				return;
			}

			var index = $list.find( '.ff-options-editor__row' ).length;
			var found = this.findField( this.selectedFieldId );
			$list.append( this.renderOptionRow( { label: '', value: '', selected: false }, index, found ? found.field : null ) );
			this.syncOptionsFromEditor();
			$list.find( '.ff-options-editor__row' ).last().find( '.ff-options-editor__label' ).trigger( 'focus' );
		},

		removeOptionRow: function ( $button ) {
			var $list = $( '#ff-field-settings .ff-options-editor__list' );
			var $row = $button.closest( '.ff-options-editor__row' );

			if ( ! $list.length || ! $row.length ) {
				return;
			}

			if ( $list.find( '.ff-options-editor__row' ).length <= 1 ) {
				$row.find( 'input' ).val( '' );
				this.syncOptionsFromEditor();
				return;
			}

			$row.remove();
			this.syncOptionsFromEditor();
		},

		getTooltip: function ( key ) {
			return ( i18n.tooltips && i18n.tooltips[ key ] ) || '';
		},

		createTrashButton: function ( config ) {
			return this.createIconButton( $.extend( {}, config, { icon: 'trash', baseClass: 'ff-builder-trash-btn' } ) );
		},

		createIconButton: function ( config ) {
			var baseClass = config.baseClass || 'ff-builder-icon-btn';
			var $btn = $( '<button>', {
				type: 'button',
				class: baseClass + ' ' + ( config.className || '' ),
				'aria-label': config.label || '',
				title: config.label || '',
				disabled: !! config.disabled,
			} );

			if ( config.attributes ) {
				Object.keys( config.attributes ).forEach( function ( key ) {
					$btn.attr( key, config.attributes[ key ] );
				} );
			}

			$btn.append( $( '<span>', { class: 'dashicons dashicons-' + config.icon, 'aria-hidden': 'true' } ) );

			return $btn;
		},

		settingsInput: function ( key, label, value, type, tooltip, placeholder ) {
			type = type || 'text';
			var $group = $( '<div>', { class: 'ff-field-settings__group' } );
			var $label = $( '<label>', { for: 'ff-setting-' + key } );
			$label.append( $( '<span>', { class: 'ff-field-settings__label-text', text: label } ) );

			if ( tooltip ) {
				$label.append(
					$( '<span>', {
						class: 'ff-field-settings__help dashicons dashicons-info',
						'data-tooltip': tooltip,
						'aria-label': tooltip,
						tabindex: '0',
						role: 'button',
					} )
				);
			}

			$group.append( $label );
			var $input = $( '<input>', {
				type: type,
				id: 'ff-setting-' + key,
				'data-setting': key,
				value: value === undefined || value === null ? '' : value,
			} );

			if ( placeholder ) {
				$input.attr( 'placeholder', placeholder );
			}

			$group.append( $input );
			return $group;
		},

		settingsSelect: function ( key, label, value, options, tooltip ) {
			var $group = $( '<div>', { class: 'ff-field-settings__group' } );
			var $label = $( '<label>', { for: 'ff-setting-' + key } );
			$label.append( $( '<span>', { class: 'ff-field-settings__label-text', text: label } ) );

			if ( tooltip ) {
				$label.append(
					$( '<span>', {
						class: 'ff-field-settings__help dashicons dashicons-info',
						'data-tooltip': tooltip,
						'aria-label': tooltip,
						tabindex: '0',
						role: 'button',
					} )
				);
			}

			$group.append( $label );

			var $select = $( '<select>', {
				id: 'ff-setting-' + key,
				'data-setting': key,
			} );

			( options || [] ).forEach( function ( option ) {
				$select.append(
					$( '<option>', {
						value: option.value,
						text: option.label,
						selected: value === option.value,
					} )
				);
			} );

			$group.append( $select );
			return $group;
		},

		settingsTextarea: function ( key, label, value, tooltip, hint ) {
			var $group = $( '<div>', { class: 'ff-field-settings__group' } );
			var $label = $( '<label>', { for: 'ff-setting-' + key } );
			$label.append( $( '<span>', { class: 'ff-field-settings__label-text', text: label } ) );

			if ( tooltip ) {
				$label.append(
					$( '<span>', {
						class: 'ff-field-settings__help dashicons dashicons-info',
						'data-tooltip': tooltip,
						'aria-label': tooltip,
						tabindex: '0',
						role: 'button',
					} )
				);
			}

			$group.append( $label );
			$group.append(
				$( '<textarea>', {
					id: 'ff-setting-' + key,
					class: 'ff-field-settings__textarea',
					'data-setting': key,
					text: value || '',
				} )
			);

			if ( hint ) {
				$group.append(
					$( '<p>', { class: 'ff-field-settings__hint description', text: hint } )
				);
			}

			return $group;
		},

		settingsCheckbox: function ( key, label, checked, tooltip ) {
			var $group = $( '<div>', { class: 'ff-field-settings__group ff-field-settings__checkbox' } );
			var $label = $( '<label>' );
			$label.append(
				$( '<input>', {
					type: 'checkbox',
					'data-setting': key,
					checked: !! checked,
				} )
			);
			$label.append( $( '<span>', { class: 'ff-field-settings__label-text', text: label } ) );

			if ( tooltip ) {
				$label.append(
					$( '<span>', {
						class: 'ff-field-settings__help dashicons dashicons-info',
						'data-tooltip': tooltip,
						'aria-label': tooltip,
						tabindex: '0',
						role: 'button',
					} )
				);
			}

			$group.append( $label );
			return $group;
		},

		updateFieldSetting: function ( $input ) {
			if ( ! this.selectedFieldId ) {
				return;
			}
			var found = this.findField( this.selectedFieldId );
			if ( ! found ) {
				return;
			}

			var key = $input.data( 'setting' );
			var value;

			if ( $input.attr( 'type' ) === 'checkbox' ) {
				value = $input.is( ':checked' );
			} else {
				value = $input.val();
			}

			if ( key === 'required' ) {
				found.field[ key ] = value;
			} else if ( [ 'minLength', 'maxLength', 'rows', 'maxFileSize', 'minSelections', 'maxSelections', 'minFiles', 'maxFiles' ].indexOf( key ) !== -1 ) {
				found.field[ key ] = value === '' ? '' : parseInt( value, 10 );
			} else if ( [ 'min', 'max' ].indexOf( key ) !== -1 ) {
				found.field[ key ] = value === '' ? '' : parseFloat( value );
			} else {
				found.field[ key ] = value;
			}

			if ( key === 'allowMultiple' && ! value ) {
				found.field.minSelections = '';
				found.field.maxSelections = '';
				found.field.minFiles = '';
				found.field.maxFiles = '';
			}

			this.highlightSelected();
			this.updateFieldPreview( found.field );
			this.updateMergeTagsList();

			if ( key === 'allowMultiple' ) {
				this.renderSettingsPanel();
				this.highlightSelected();
			}
		},

		updateFieldPreview: function ( field ) {
			var $el = $( '.ff-builder-field[data-field-id="' + field.id + '"]' );
			if ( ! $el.length ) {
				return;
			}
			var required = field.required ? ' *' : '';
			var previewLabel = field.type === 'consent' || field.type === 'content'
				? ( field.type === 'content'
					? ( field.contentText || field.label || field.type )
					: ( field.consentText || field.label || field.type ) )
				: ( field.label || field.type );
			$el.find( '.ff-builder-field__label' ).empty();

			if ( field.type === 'consent' || field.type === 'content' ) {
				$el.find( '.ff-builder-field__label' ).html( this.sanitizeConsentPreview( previewLabel + required ) );
			} else {
				$el.find( '.ff-builder-field__label' ).text( previewLabel + required );
			}
			var meta = this.getFieldLabel( field.type ) + ( field.name ? ' · ' + field.name : '' );

			if ( field.type === 'select' || field.type === 'radio' ) {
				var optionCount = ( field.options || [] ).filter( function ( opt ) {
					return opt && ( opt.label || opt.value );
				} ).length;
				meta += ' · ' + optionCount + ' ' + ( i18n.options || 'options' ).toLowerCase();

				if ( field.type === 'radio' && field.allowMultiple ) {
					meta += ' · ' + ( i18n.allowMultiple || 'multiple' ).toLowerCase();
				}
			}

			if ( field.type === 'file' && field.allowMultiple ) {
				meta += ' · ' + ( i18n.allowMultipleFiles || 'multiple files' ).toLowerCase();

				if ( field.minFiles ) {
					meta += ' · min ' + field.minFiles;
				}

				if ( field.maxFiles ) {
					meta += ' · max ' + field.maxFiles;
				}
			}

			if ( field.type === 'file' && field.fileButtonText ) {
				meta += ' · "' + field.fileButtonText + '"';
			}

			if ( field.htmlId ) {
				meta += ' · #' + field.htmlId;
			}

			$el.find( '.ff-builder-field__meta' ).text( meta );
		},

		updateShortcodePreviews: function () {
			var formId = config.formId;
			var btnText = $( '#ff-form-shortcodeButtonText' ).val() || 'Open form';
			var btnClass = $( '#ff-form-shortcodeButtonClass' ).val() || 'button';
			var trigger = ( $( '#ff-form-shortcodeTriggerClass' ).val() || '' ).trim();

			if ( trigger && trigger.charAt( 0 ) !== '.' && trigger.charAt( 0 ) !== '#' ) {
				trigger = '.' + trigger;
			}

			$( '#ff-shortcode-inline, #ff-shortcode-inline-metabox' ).text( '[smart_form id="' + formId + '" display="inline"]' );

			var buttonShortcode = '[smart_form id="' + formId + '" display="button" button_text="' + btnText + '"';
			if ( btnClass ) {
				buttonShortcode += ' button_class="' + btnClass + '"';
			}
			buttonShortcode += ']';
			$( '#ff-shortcode-button, #ff-shortcode-button-metabox' ).text( buttonShortcode );

			var triggerShortcode = '[smart_form id="' + formId + '" display="trigger"';
			if ( trigger ) {
				triggerShortcode += ' trigger="' + trigger + '"';
			}
			triggerShortcode += ']';
			$( '#ff-shortcode-trigger, #ff-shortcode-trigger-metabox' ).text( triggerShortcode );
		},

		sanitizePathSegment: function ( value ) {
			return String( value || '' )
				.toLowerCase()
				.replace( /[^a-z0-9._-]+/g, '-' )
				.replace( /^-+|-+$/g, '' );
		},

		updateUploadPathPreview: function () {
			var custom = ( $( '#ff-form-uploadPath' ).val() || '' ).trim();
			var pattern = custom || config.globalUploadPath || 'fast-forms/{form_slug}';
			var titleInput = ( $( '#title' ).val() || '' ).trim();
			var slug = config.formSlug || ( 'form-' + config.formId );
			var titleSlug = this.sanitizePathSegment( titleInput ) || slug;

			if ( titleInput ) {
				slug = this.sanitizePathSegment( titleInput ) || slug;
			}

			var resolved = pattern
				.replace( /\{form_slug\}/g, slug )
				.replace( /\{form_id\}/g, String( config.formId ) )
				.replace( /\{form_title\}/g, titleSlug );

			resolved = resolved
				.split( '/' )
				.map( this.sanitizePathSegment )
				.filter( Boolean )
				.join( '/' );

			if ( ! resolved ) {
				resolved = 'fast-forms/form-' + config.formId;
			}

			$( '#ff-upload-path-effective' ).text( resolved );

			if ( config.uploadsBaseUrl ) {
				$( '#ff-upload-path-url' ).text( config.uploadsBaseUrl + resolved );
			}
		},

		copyShortcode: function ( $button ) {
			var self = this;
			var selector = $button.attr( 'data-ff-copy' );
			var text = selector ? $( selector ).text() : '';

			if ( ! text ) {
				return;
			}

			var onSuccess = function () {
				$button.addClass( 'is-copied' );
				window.setTimeout( function () {
					$button.removeClass( 'is-copied' );
				}, 1200 );
				self.showCopyToast( i18n.shortcodeCopied || 'Shortcode copied to clipboard!' );
			};

			var onError = function () {
				self.showCopyToast( i18n.shortcodeCopyFailed || 'Could not copy shortcode. Please copy it manually.', true );
			};

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( onSuccess ).catch( onError );
				return;
			}

			var $temp = $( '<textarea readonly aria-hidden="true">' ).css( {
				position: 'fixed',
				left: '-9999px',
				top: '0',
			} ).val( text ).appendTo( 'body' );

			$temp[ 0 ].select();

			try {
				if ( document.execCommand( 'copy' ) ) {
					onSuccess();
				} else {
					onError();
				}
			} catch ( err ) {
				onError();
			}

			$temp.remove();
		},

		showCopyToast: function ( message, isError ) {
			var $toast = $( '#ff-copy-toast' );

			if ( ! $toast.length ) {
				$toast = $( '<div>', {
					id: 'ff-copy-toast',
					class: 'ff-copy-toast',
					role: 'status',
					'aria-live': 'polite',
				} ).appendTo( 'body' );
			}

			$toast.removeClass( 'is-visible is-error' );

			if ( isError ) {
				$toast.addClass( 'is-error' ).css( 'background', '#d63638' );
			} else {
				$toast.css( 'background', '#00a32a' );
			}

			$toast.empty().append(
				$( '<span>', { class: 'dashicons ' + ( isError ? 'dashicons-warning' : 'dashicons-yes-alt' ), 'aria-hidden': 'true' } ),
				$( '<span>', { text: message } )
			);

			window.requestAnimationFrame( function () {
				$toast.addClass( 'is-visible' );
			} );

			window.clearTimeout( this.copyToastTimer );
			this.copyToastTimer = window.setTimeout( function () {
				$toast.removeClass( 'is-visible' );
			}, 2800 );
		},

		updateMergeTagsList: function () {
			var tags = [ '{form:title}', '{form:id}', '{entry:id}', '{entry:date}', '{all_fields}' ];
			var fieldTags = [];
			var seen = {};

			tags.forEach( function ( tag ) {
				seen[ tag ] = true;
			} );

			( this.schema.rows || [] ).forEach( function ( row ) {
				( row.columns || [] ).forEach( function ( col ) {
					( col.fields || [] ).forEach( function ( field ) {
						if ( ! field || field.type === 'submit' || field.type === 'content' ) {
							return;
						}

						var key = field.name || field.id;
						if ( ! key ) {
							return;
						}

						var tag = '{field:' + key + '}';
						if ( seen[ tag ] ) {
							return;
						}

						seen[ tag ] = true;
						fieldTags.push( {
							tag: tag,
							label: ( field.label || '' ).trim(),
						} );
					} );
				} );
			} );

			var html = tags.map( function ( tag ) {
				return '<code>' + tag + '</code>';
			} ).concat( fieldTags.map( function ( item ) {
				var text = item.label ? item.label + ' ' + item.tag : item.tag;
				return '<code>' + text + '</code>';
			} ) ).join( ' ' );

			$( '.ff-merge-tags-list__items' ).html( html );
		},

		setSaveStatus: function ( message, type ) {
			var $status = $( '#ff-save-status' );
			$status.text( message ).removeClass( 'is-success is-error' );
			if ( type ) {
				$status.addClass( 'is-' + type );
			}
		},

		encodeSchemaPayload: function ( schemaJson ) {
			return window.btoa( unescape( encodeURIComponent( schemaJson ) ) )
				.replace( /\+/g, '-' )
				.replace( /\//g, '_' )
				.replace( /=+$/g, '' );
		},

		syncToForm: function () {
			if ( ! this.isReady ) {
				return;
			}

			this.syncRowsOrder();
			this.syncFieldsOrder();

			var schemaJson = JSON.stringify( this.schema );

			$( '#ff-schema-json' ).val( schemaJson );

			try {
				$( '#ff-schema-json-b64' ).val( this.encodeSchemaPayload( schemaJson ) );
			} catch ( error ) {
				$( '#ff-schema-json-b64' ).val( '' );
				this.debug( 'Schema base64 encode failed', {
					formId: config.formId,
					error: error && error.message ? error.message : String( error ),
				} );
			}

			$( '#ff-form-settings-json' ).val( JSON.stringify( this.collectFormSettings() ) );
			this.debug( 'Schema synced to form', {
				formId: config.formId,
				fieldCount: this.countFields( this.schema ),
				jsonLen: schemaJson.length,
				b64Len: $( '#ff-schema-json-b64' ).val().length,
			} );
		},

		collectFormSettings: function () {
			var settings = {
				email: {},
				validation: {},
				notifications: {},
				form: {},
			};

			$( '[data-ff-group][data-ff-setting]' ).each( function () {
				var $el = $( this );
				var group = $el.data( 'ff-group' );
				var key = $el.data( 'ff-setting' );
				var inputType = $el.data( 'ff-input-type' ) || $el.attr( 'type' ) || '';

				if ( ! settings[ group ] ) {
					settings[ group ] = {};
				}

				if ( inputType === 'checkbox' ) {
					settings[ group ][ key ] = $el.is( ':checked' );
				} else if ( inputType === 'number' ) {
					settings[ group ][ key ] = $el.val() === '' || $el.val() === null ? 0 : parseInt( $el.val(), 10 );
				} else {
					settings[ group ][ key ] = $el.val();
				}
			} );

			return settings;
		},

		save: function () {
			var self = this;

			if ( ! this.isReady ) {
				return;
			}

			if ( this.isSaving ) {
				return;
			}

			this.syncToForm();

			this.isSaving = true;
			this.setSaveStatus( i18n.saving, '' );
			$( '#ff-save-form' ).prop( 'disabled', true );

			this.debug( 'Save started', {
				formId: config.formId,
				fieldCount: this.countFields( this.schema ),
				rowCount: ( this.schema.rows || [] ).length,
			} );

			$.ajax( {
				url: config.restUrl + '/forms/' + config.formId,
				method: 'PUT',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', config.nonce );
				},
				contentType: 'application/json',
				data: JSON.stringify( {
					schema: self.schema,
					formSettings: self.collectFormSettings(),
				} ),
			} )
				.done( function ( response ) {
					if ( response && response.schemaVersion ) {
						self.schemaVersion = response.schemaVersion;
					}
					self.debug( 'Save success', {
						formId: config.formId,
						schemaVersion: self.schemaVersion,
						responseFieldCount: self.countFields( response.schema || {} ),
					} );
					self.setSaveStatus( i18n.saved, 'success' );
				} )
				.fail( function ( xhr ) {
					self.debug( 'Save failed', {
						formId: config.formId,
						status: xhr.status,
						response: xhr.responseText,
					} );
					self.setSaveStatus( i18n.saveError, 'error' );
				} )
				.always( function () {
					self.isSaving = false;
					$( '#ff-save-form' ).prop( 'disabled', false );
				} );
		},
	};

	$( function () {
		if ( $( '#ff-form-builder' ).length ) {
			Builder.init();
		}
	} );
}( jQuery ) );
