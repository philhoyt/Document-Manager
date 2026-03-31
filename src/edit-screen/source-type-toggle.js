/**
 * Source type toggle — shows/hides the File or URL meta boxes
 * based on which radio button is selected.
 */

export function initSourceTypeToggle() {
	const radios = document.querySelectorAll(
		'input[name="_ph_document_source_type"]'
	);
	const fileWrap = document.getElementById( 'ph-doc-file-wrap' );
	const urlWrap = document.getElementById( 'ph-doc-url-wrap' );

	if ( ! radios.length || ! fileWrap || ! urlWrap ) {
		return;
	}

	function applyVisibility( value ) {
		if ( 'file' === value ) {
			fileWrap.style.display = '';
			fileWrap.removeAttribute( 'aria-hidden' );
			urlWrap.style.display = 'none';
			urlWrap.setAttribute( 'aria-hidden', 'true' );
		} else {
			urlWrap.style.display = '';
			urlWrap.removeAttribute( 'aria-hidden' );
			fileWrap.style.display = 'none';
			fileWrap.setAttribute( 'aria-hidden', 'true' );
		}
	}

	// Set initial state from whichever radio is checked.
	radios.forEach( ( radio ) => {
		if ( radio.checked ) {
			applyVisibility( radio.value );
		}

		radio.addEventListener( 'change', ( e ) => {
			applyVisibility( e.target.value );
		} );
	} );
}
