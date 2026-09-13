/**
 * Lucide icons (https://lucide.dev), ISC License,
 * Copyright (c) 2026 Lucide Icons and Contributors.
 *
 * The shapes only, taken from lucide-react 1.45.0. The `<svg>` around them is
 * added by `icon()`, and the same shapes are mirrored in `Helpers::icon()` in
 * the PHP template, so an icon looks the same whichever side renders it.
 */

import { TIconName } from '../types';

export const iconShapes: Record<TIconName, string> = {
	'menu': '<path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/>',
	'folder': '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
	'folder-plus': '<path d="M12 10v6"/><path d="M9 13h6"/><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
	'file': '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/>',
	'file-image': '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><circle cx="10" cy="12" r="2"/><path d="m20 17-1.296-1.296a2.41 2.41 0 0 0-3.408 0L9 22"/>',
	'file-video': '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M15.033 13.44a.647.647 0 0 1 0 1.12l-4.065 2.352a.645.645 0 0 1-.968-.56v-4.704a.645.645 0 0 1 .967-.56z"/>',
	'corner-left-up': '<path d="M14 9 9 4 4 9"/><path d="M20 20h-7a4 4 0 0 1-4-4V4"/>',
	'download': '<path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>',
	'trash-2': '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
	'x': '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
	'chevron-left': '<path d="m15 18-6-6 6-6"/>',
	'chevron-right': '<path d="m9 18 6-6-6-6"/>',
	'chevron-up': '<path d="m18 15-6-6-6 6"/>',
	'chevron-down': '<path d="m6 9 6 6 6-6"/>',
	'search': '<path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/>',
	'upload': '<path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>',
	'panel-right': '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/>',
	'copy': '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
	'images': '<path d="m22 11-1.296-1.296a2.4 2.4 0 0 0-3.408 0L11 16"/><path d="M4 8a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2"/><circle cx="13" cy="7" r="1" fill="currentColor"/><rect x="8" y="2" width="14" height="14" rx="2"/>',
	'sliders-horizontal': '<path d="M10 5H3"/><path d="M12 19H3"/><path d="M14 3v4"/><path d="M16 17v4"/><path d="M21 12h-9"/><path d="M21 19h-5"/><path d="M21 5h-7"/><path d="M8 10v4"/><path d="M8 12H3"/>',
	'log-out': '<path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
	'arrow-up': '<path d="m5 12 7-7 7 7"/><path d="M12 19V5"/>',
	'arrow-down': '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
	'loader-circle': '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>'
};

/**
 * The markup of an icon.
 *
 * Hidden from assistive technology and from the pointer: the control it sits
 * in carries the label, and a click has to land on that control, because
 * several handlers here compare `event.target` against the element they bound
 */
export const icon = (name: TIconName): string =>
{
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"'
		+ ' stroke="currentColor" stroke-width="2" stroke-linecap="round"'
		+ ` stroke-linejoin="round" aria-hidden="true" class="icon icon-${name}">`
		+ iconShapes[name]
		+ '</svg>';
};

/**
 * An icon as an element, ready to insert
 */
export const iconElement = (name: TIconName): SVGElement =>
{
	const template = document.createElement('template');

	template.innerHTML = icon(name);

	return template.content.firstChild as SVGElement;
};
