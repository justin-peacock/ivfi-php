import {
	EventTargetEventHooks
} from '../module-event-hooks';

/**
 * Extension for preview anchors
 */
export interface IPreviewAnchor extends HTMLElement {
	itemIndex?: number;
}

/**
 * Extension for media indexing
 */
export interface ITableRowMI extends HTMLElement {
	_mediaIndex?: number;
}

/**
 * On preview load types
 */
export type TOnPreviewLoad = {
	loaded: boolean;
	type: string;
	audible: boolean;
	element: HTMLVideoElement | HTMLImageElement;
	src: string;
	timestamp?: number;
};

/**
 * Preview options
 */
export type TPreviewOptions = {
	delay?: number;
	cursor?: boolean;
	encodeAll?: boolean;
	/** Reads the media source directly, instead of from the element's
	 * `data-src`, `src` or `href` attribute */
	source?: string;
	force?: {
		extension?: string | number;
		type?: string | number;
	} | null;
	on?: {
		onLoaded: (data: TOnPreviewLoad) => void;
	} | null;
};

export type TExtensionArray = {
	image: Array<string>;
	video: Array<string>;
};

/**
 * `galleryItemChanged` event data
 */
export type TPayloadgalleryItemChanged = {
	source: string;
	index: number;
	image: HTMLElement | undefined;
	video: HTMLElement | undefined;
};

/**
 * Global `window` extensions
 */
export interface IWindowGlobals extends Window {
	eventHooks?: EventTargetEventHooks['eventHooks'];
}

/**
 * Global `document` extensions
 */
export interface IDocumentGlobals extends Document {
	eventHooks?: EventTargetEventHooks['eventHooks'];
}
/**
 * Names of the bundled icons, see `helpers/icons.ts`
 */
export type TIconName = 'menu' | 'folder' | 'folder-plus' | 'file' | 'file-image'
	| 'file-video' | 'corner-left-up' | 'download' | 'trash-2' | 'x'
	| 'chevron-left' | 'chevron-right' | 'chevron-up' | 'chevron-down' | 'search'
	| 'upload' | 'panel-right' | 'copy' | 'images' | 'sliders-horizontal'
	| 'log-out' | 'arrow-up' | 'arrow-down' | 'loader-circle';
