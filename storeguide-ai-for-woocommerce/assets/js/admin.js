( function () {
	const tabWrapper = document.querySelector( '.storeguide-ai-tabs' );
	if ( ! tabWrapper ) {
		return;
	}

	const activeTab = tabWrapper.querySelector( '.nav-tab-active' );
	if ( activeTab ) {
		activeTab.scrollIntoView( { block: 'nearest', inline: 'center' } );
	}

	const tableBody = document.getElementById( 'storeguide-ai-coupon-rules-body' );
	const addButton = document.getElementById( 'storeguide-ai-add-coupon-rule' );
	const rowTemplate = document.getElementById( 'storeguide-ai-coupon-rule-row-template' );
	const hasJquery = typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn !== 'undefined';
	const hasSelect2 = hasJquery && typeof window.jQuery.fn.select2 === 'function';
	const hasSelectWoo = hasJquery && typeof window.jQuery.fn.selectWoo === 'function';
	let rowIndex = tableBody ? tableBody.querySelectorAll( 'tr' ).length : 0;

	const initCouponSelect = ( selectEl ) => {
		if ( ! selectEl || !window.StoreGuideAIAdmin || ( ! hasSelect2 && ! hasSelectWoo ) ) {
			return;
		}
		const $select = window.jQuery( selectEl );
		const method = hasSelectWoo ? 'selectWoo' : 'select2';
		$select[ method ]( {
			width: '300px',
			placeholder: selectEl.getAttribute( 'data-placeholder' ) || 'Search coupon',
			allowClear: true,
			ajax: {
				url: StoreGuideAIAdmin.ajaxUrl,
				dataType: 'json',
				delay: 250,
				data: ( params ) => ( {
					action: 'storeguide_ai_search_coupons',
					nonce: StoreGuideAIAdmin.nonce,
					term: params.term || ''
				} ),
				processResults: ( data ) => data && data.results ? data : { results: [] }
			},
			minimumInputLength: 1
		} );
	};

	if ( tableBody ) {
		tableBody.querySelectorAll( '.storeguide-ai-coupon-select' ).forEach( initCouponSelect );
	}

	if ( addButton && rowTemplate && tableBody ) {
		addButton.addEventListener( 'click', () => {
			const html = rowTemplate.innerHTML.split( '__INDEX__' ).join( String( rowIndex ) );
			rowIndex += 1;
			const wrapper = document.createElement( 'tbody' );
			wrapper.innerHTML = html.trim();
			const row = wrapper.firstElementChild;
			if ( ! row ) {
				return;
			}
			tableBody.appendChild( row );
			const selectEl = row.querySelector( '.storeguide-ai-coupon-select' );
			initCouponSelect( selectEl );
		} );
	}

	document.addEventListener( 'click', ( event ) => {
		const target = event.target;
		if ( !target || !target.classList || !target.classList.contains( 'storeguide-ai-remove-coupon-rule' ) ) {
			return;
		}
		const row = target.closest( 'tr' );
		if ( row ) {
			row.remove();
		}
	} );
}() );
