( function () {
	const widget = document.getElementById( 'storeguide-ai-widget' );
	if ( ! widget ) {
		return;
	}

	const toggle = widget.querySelector( '.storeguide-ai-toggle' );
	const panel = widget.querySelector( '.storeguide-ai-panel' );
	const form = widget.querySelector( '.storeguide-ai-form' );
	const input = widget.querySelector( '#storeguide-ai-input' );
	const messages = widget.querySelector( '.storeguide-ai-messages' );
	const stateKey = 'storeguide_ai_chat_state_v1';
	const maxMessageLength = 1200;

	if ( ! toggle || ! panel || ! form || ! input || ! messages ) {
		return;
	}

	const loadState = () => {
		try {
			const raw = sessionStorage.getItem( stateKey );
			if ( ! raw ) {
				return null;
			}
			const parsed = JSON.parse( raw );
			if ( ! parsed || ! Array.isArray( parsed.entries ) ) {
				return null;
			}
			return parsed;
		} catch ( e ) {
			return null;
		}
	};

	const saveState = ( state ) => {
		try {
			sessionStorage.setItem( stateKey, JSON.stringify( state ) );
		} catch ( e ) {
			// Ignore storage errors (private mode, quota).
		}
	};

	const chatState = loadState() || {
		isOpen: false,
		entries: []
	};
	let closeTimer = null;

	if ( ! chatState.entries.length ) {
		const initial = messages.querySelector( '.storeguide-ai-message-assistant' );
		if ( initial && initial.textContent ) {
			chatState.entries.push( { type: 'message', role: 'assistant', text: initial.textContent } );
			saveState( chatState );
		}
	}

	const appendMessage = ( text, role, persist = true ) => {
		const item = document.createElement( 'p' );
		item.className = 'storeguide-ai-message storeguide-ai-message-' + role;
		item.textContent = text;
		messages.appendChild( item );
		messages.scrollTop = messages.scrollHeight;

		if ( persist ) {
			chatState.entries.push( { type: 'message', role, text } );
			saveState( chatState );
		}
	};

	const showTypingIndicator = () => {
		const wrapper = document.createElement( 'div' );
		wrapper.className = 'storeguide-ai-message storeguide-ai-message-assistant storeguide-ai-typing';
		wrapper.innerHTML = '<span class="storeguide-ai-typing-dots"><span></span><span></span><span></span></span>';
		messages.appendChild( wrapper );
		messages.scrollTop = messages.scrollHeight;
		return wrapper;
	};

	const appendResults = ( results, persist = true ) => {
		if ( ! Array.isArray( results ) || ! results.length ) {
			return;
		}
		const display = StoreGuideAIConfig.widgetDisplay || {};
		const fields = Array.isArray( display.fields ) ? display.fields : [ 'thumbnail', 'name', 'price', 'link' ];

		const list = document.createElement( 'div' );
		list.className = 'storeguide-ai-results';

		results.forEach( ( item ) => {
			const card = document.createElement( 'article' );
			card.className = 'storeguide-ai-result-card';

			if ( fields.includes( 'thumbnail' ) && item.thumbnail_url ) {
				const img = document.createElement( 'img' );
				img.src = item.thumbnail_url;
				img.alt = item.title || 'Product';
				img.className = 'storeguide-ai-result-thumb';
				card.appendChild( img );
			}

			const body = document.createElement( 'div' );
			body.className = 'storeguide-ai-result-body';
			const hasName = fields.includes( 'name' );
			const hasLink = fields.includes( 'link' );
			const hasUrl = !! item.product_url;

			if ( hasName ) {
				if ( hasUrl ) {
					const titleLink = document.createElement( 'a' );
					titleLink.href = item.product_url;
					titleLink.textContent = item.title || 'Product';
					titleLink.target = '_blank';
					titleLink.rel = 'noopener noreferrer';
					titleLink.className = 'storeguide-ai-result-title-link';
					body.appendChild( titleLink );
				} else {
					const title = document.createElement( 'strong' );
					title.textContent = item.title || 'Product';
					body.appendChild( title );
				}
			}

			if ( fields.includes( 'price' ) ) {
				const price = document.createElement( 'div' );
				price.className = 'storeguide-ai-result-price';
				price.textContent = item.price_html || ( item.price !== null && item.price !== undefined ? String( item.price ) : '-' );
				body.appendChild( price );
			}

			if ( fields.includes( 'availability' ) ) {
				const avail = document.createElement( 'div' );
				avail.className = 'storeguide-ai-result-stock';
				avail.textContent = item.stock_status === 'instock' ? 'Dostepny' : 'Niedostepny';
				body.appendChild( avail );
			}

			if ( hasLink && hasUrl && ! hasName ) {
				const link = document.createElement( 'a' );
				link.href = item.product_url;
				link.textContent = 'Zobacz produkt';
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
				link.className = 'storeguide-ai-result-link';
				body.appendChild( link );
			}

			card.appendChild( body );
			list.appendChild( card );
		} );

		messages.appendChild( list );
		messages.scrollTop = messages.scrollHeight;

		if ( persist ) {
			chatState.entries.push( { type: 'results', items: results } );
			saveState( chatState );
		}
	};

	const appendRelated = ( related, persist = true ) => {
		const display = StoreGuideAIConfig.widgetDisplay || {};
		if ( ! display.showRelated || ! Array.isArray( related ) || ! related.length ) {
			return;
		}
		appendMessage( 'Produkty powiazane:', 'assistant', persist );
		appendResults( related, persist );
	};

	const restoreHistory = () => {
		if ( ! chatState.entries.length ) {
			return;
		}
		messages.innerHTML = '';
		chatState.entries.forEach( ( entry ) => {
			if ( entry.type === 'message' ) {
				appendMessage( entry.text || '', entry.role || 'assistant', false );
				return;
			}
			if ( entry.type === 'results' ) {
				appendResults( entry.items || [], false );
			}
		} );
	};

	restoreHistory();
	const openPanel = ( focusInput = false ) => {
		if ( closeTimer ) {
			window.clearTimeout( closeTimer );
			closeTimer = null;
		}
		panel.removeAttribute( 'hidden' );
		panel.classList.remove( 'is-closing' );
		window.requestAnimationFrame( () => {
			panel.classList.add( 'is-open' );
			widget.classList.add( 'is-open' );
		} );
		toggle.setAttribute( 'aria-expanded', 'true' );
		chatState.isOpen = true;
		saveState( chatState );
		if ( focusInput ) {
			window.setTimeout( () => input.focus(), 120 );
		}
	};

	const closePanel = () => {
		panel.classList.remove( 'is-open' );
		panel.classList.add( 'is-closing' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		chatState.isOpen = false;
		saveState( chatState );

		const finishClose = () => {
			panel.setAttribute( 'hidden', 'hidden' );
			panel.classList.remove( 'is-closing' );
			widget.classList.remove( 'is-open' );
		};

		const onTransitionEnd = ( event ) => {
			if ( event.target !== panel ) {
				return;
			}
			panel.removeEventListener( 'transitionend', onTransitionEnd );
			if ( ! chatState.isOpen ) {
				finishClose();
			}
		};

		panel.addEventListener( 'transitionend', onTransitionEnd );
		closeTimer = window.setTimeout( () => {
			panel.removeEventListener( 'transitionend', onTransitionEnd );
			if ( ! chatState.isOpen ) {
				finishClose();
			}
		}, 320 );
	};

	if ( chatState.isOpen ) {
		openPanel( false );
	}

	toggle.addEventListener( 'click', () => {
		const isHidden = panel.hasAttribute( 'hidden' );
		if ( isHidden ) {
			openPanel( true );
			return;
		}
		closePanel();
	} );

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();

		const text = input.value.trim();
		if ( ! text ) {
			return;
		}
		if ( text.length > maxMessageLength ) {
			appendMessage( 'Wiadomosc jest za dluga (maks. 1200 znakow).', 'assistant' );
			return;
		}

		appendMessage( text, 'user' );
		input.value = '';
		const typing = showTypingIndicator();

		try {
			const htmlLang = ( document.documentElement && document.documentElement.lang ) ? document.documentElement.lang : '';
			const language = ( htmlLang || StoreGuideAIConfig.lang || 'en' ).split( '-' )[0].toLowerCase();

			const response = await fetch( StoreGuideAIConfig.endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': StoreGuideAIConfig.nonce
				},
				body: JSON.stringify( { message: text, language } )
			} );
			let payload = {};
			try {
				payload = await response.json();
			} catch ( e ) {
				payload = { error: 'Nieprawidlowa odpowiedz serwera.' };
			}
			if ( typing && typing.parentNode ) {
				typing.parentNode.removeChild( typing );
			}

			if ( ! response.ok ) {
				appendMessage( payload.error || 'Request failed.', 'assistant' );
				return;
			}

			appendMessage( payload.message || 'No response.', 'assistant' );
			appendResults( payload.results || [] );
			appendRelated( payload.related || [] );
		} catch ( error ) {
			if ( typing && typing.parentNode ) {
				typing.parentNode.removeChild( typing );
			}
			appendMessage( 'Network error. Please try again.', 'assistant' );
		}
	} );
}() );
