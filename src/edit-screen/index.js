import './index.scss';
import { initSourceTypeToggle } from './source-type-toggle.js';
import { initMediaPicker } from './media-picker.js';
import { initCopyLink } from './copy-link.js';

document.addEventListener( 'DOMContentLoaded', () => {
	initSourceTypeToggle();
	initMediaPicker();
	initCopyLink();
} );
