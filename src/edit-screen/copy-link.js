/**
 * Copy Link button — copies the document permalink to the clipboard.
 */

export function initCopyLink() {
	const btn = document.getElementById( 'ph-doc-copy-link' );
	if ( ! btn ) {
		return;
	}

	const url = btn.dataset.url;
	if ( ! url ) {
		return;
	}

	const originalText = btn.textContent;
	const copiedText =
		window.phDocumentManager?.copiedText || 'Copied!';

	btn.addEventListener( 'click', () => {
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard
				.writeText( url )
				.then( () => showCopied( btn, copiedText, originalText ) )
				.catch( () => fallbackCopy( url, btn, copiedText, originalText ) );
		} else {
			fallbackCopy( url, btn, copiedText, originalText );
		}
	} );
}

/**
 * Show a brief "Copied!" confirmation on the button.
 *
 * @param {HTMLElement} btn
 * @param {string}      copiedText
 * @param {string}      originalText
 */
function showCopied( btn, copiedText, originalText ) {
	btn.textContent = copiedText;
	setTimeout( () => {
		btn.textContent = originalText;
	}, 2000 );
}

/**
 * execCommand fallback for older browsers.
 *
 * @param {string}      url
 * @param {HTMLElement} btn
 * @param {string}      copiedText
 * @param {string}      originalText
 */
function fallbackCopy( url, btn, copiedText, originalText ) {
	const textarea = document.createElement( 'textarea' );
	textarea.value = url;
	textarea.style.position = 'fixed';
	textarea.style.opacity = '0';
	document.body.appendChild( textarea );
	textarea.focus();
	textarea.select();

	try {
		document.execCommand( 'copy' );
		showCopied( btn, copiedText, originalText );
	} catch ( err ) {
		// Silent fail — the URL is already visible on screen.
	} finally {
		document.body.removeChild( textarea );
	}
}
