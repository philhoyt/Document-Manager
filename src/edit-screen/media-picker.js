/**
 * Media picker — integrates with wp.media to allow selecting or uploading
 * a file attachment and updating the hidden file ID input + display info.
 */

import { speak } from '@wordpress/a11y';

export function initMediaPicker() {
	const selectBtn = document.getElementById( 'ph-doc-select-file' );
	const fileIdInput = document.getElementById( 'ph-doc-file-id' );
	const fileInfo = document.getElementById( 'ph-doc-file-info' );

	if ( ! selectBtn || ! fileIdInput || ! fileInfo ) {
		return;
	}

	if ( ! window.wp || ! window.wp.media ) {
		return;
	}

	let frame;

	// Handle remove button if it exists in the DOM.
	document.addEventListener( 'click', ( e ) => {
		if ( e.target && e.target.id === 'ph-doc-remove-file' ) {
			fileIdInput.value = '0';
			fileInfo.className = 'ph-doc-file-info';
			const noFileText =
				window.phDocumentManager?.noFileText || 'No file selected.';
			fileInfo.innerHTML =
				'<span class="ph-doc-no-file">' + noFileText + '</span>';
			speak( noFileText, 'polite' );
		}
	} );

	selectBtn.addEventListener( 'click', () => {
		if ( frame ) {
			frame.open();
			return;
		}

		frame = window.wp.media( {
			title:
				window.phDocumentManager?.mediaTitle ||
				'Select or Upload Document',
			button: {
				text: window.phDocumentManager?.mediaButton || 'Use This File',
			},
			multiple: false,
		} );

		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();

			fileIdInput.value = attachment.id;

			const fileName = attachment.filename || attachment.title || '';
			const fileSize = attachment.filesizeHumanReadable || '';
			const fileDate = attachment.dateFormatted || '';

			let html = `<span class="ph-doc-file-name">${ escapeHtml(
				fileName
			) }</span>`;
			if ( fileSize ) {
				html += ` <span class="ph-doc-file-size">${ escapeHtml(
					fileSize
				) }</span>`;
			}
			if ( fileDate ) {
				html += ` <span class="ph-doc-file-date">${ escapeHtml(
					fileDate
				) }</span>`;
			}
			html += ` <button type="button" id="ph-doc-remove-file" class="button-link ph-doc-remove-link">${ escapeHtml(
				window.phDocumentManager?.removeText || 'Remove'
			) }</button>`;

			fileInfo.className = 'ph-doc-file-info has-file';
			fileInfo.innerHTML = html;

			// The info block is rewritten in place, which screen readers do not
			// announce on their own.
			speak(
				( window.phDocumentManager?.fileSelectedText ||
					'File selected:' ) +
					' ' +
					fileName,
				'polite'
			);
		} );

		frame.open();
	} );
}

/**
 * Minimal HTML escaping for dynamic content.
 *
 * @param {string} str Untrusted string to escape.
 * @return {string} The string with HTML-significant characters escaped.
 */
function escapeHtml( str ) {
	return String( str )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}
