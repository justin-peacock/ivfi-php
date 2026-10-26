/**
 * Configuration (user) object
 */
export type TUserClient = {
	set?: (
		client: TUserStorage | TUserClient,
		options?: TUserStorage
	) => void;
	get?: () => TUserStorage;
	getDefaults?: () => TUserStorage;
	gallery?: {
		listAlignment?: number | boolean;
	};
	style?: {
		compact?: boolean;
	};
	timezoneOffset?: number | boolean;
	sort?: {
		ascending?: boolean | number;
		row?: number;
	}
};

/**
 * User storage (cookies)
 */
export type TUserStorage = {
	gallery?: {
		reverseOptions?: boolean;
		fitContent?: boolean;
		autoplay?: boolean;
		listAlignment?: number;
		listWidth?: number | boolean;
		listState?: number;
		volume?: number;
	};
	style?: {
		compact?: boolean;
		theme?: boolean | string;
	};
	readme?: {
		toggled?: boolean;
	};
};

/**
 * Upload settings, as the backend describes them.
 *
 * Everything past `enabled` is only sent to a client the server has already
 * decided may upload, so the rest is absent whenever `enabled` is false
 */
export type TConfigUpload = {
	enabled?: boolean;
	token?: string;
	action?: string;
	/** Whether directories may be created as well as files uploaded */
	directories?: boolean;
	directoryAction?: string;
	/** Whether files and empty directories may be deleted */
	delete?: boolean;
	deleteAction?: string;
	fields?: {
		action?: string;
		file?: string;
		name?: string;
		token?: string;
	};
	extensions?: Array<string>;
	/** Extensions the endpoint refuses whatever the allowlist says */
	blocked?: Array<string>;
	/** Largest accepted file in bytes, where zero means no limit */
	maxSize?: number;
	overwrite?: boolean;
};

/**
 * One live share link, as the backend describes it.
 *
 * The link itself is never sent: the client assembles it from this page's own
 * origin, so the document does not have to trust the `Host` header
 */
export type TConfigShareEntry = {
	token: string;
	/** The directory it covers, slash wrapped */
	path: string;
	created: number;
	/** Unix time it lapses at, where zero means it does not */
	expires: number;
};

/**
 * Share settings, as the backend describes them.
 *
 * Everything past `enabled` is only sent to a client the server has already
 * decided may mint and revoke links, the way `upload` above is
 */
export type TConfigShare = {
	enabled?: boolean;
	token?: string;
	/** Query parameter a minted link carries the token on */
	param?: string;
	createAction?: string;
	revokeAction?: string;
	fields?: {
		action?: string;
		share?: string;
		expiry?: string;
		token?: string;
	};
	/** Whether the directory being viewed may be shared, false at the root */
	shareable?: boolean;
	/** The configured default lifetime in seconds, zero for none */
	expiry?: number;
	shares?: Array<TConfigShareEntry>;
};

/**
 * Configuration object (from backend)
 */
export interface IConfigData extends Omit<TUserStorage, 'style'> {
	mobile?: boolean;
	bust?: string;
	style?: {
		compact?: boolean;
		themes?: {
			set?: boolean | string;
		}
	};
	upload?: TConfigUpload;
	share?: TConfigShare;
}

/**
 * Outer configuration object
 */
export type TConfigCapsule = {
	init?: () => void;
	isMobile?: () => boolean;
	exists?: (
		path: string
	) => boolean;
	set?: (
		path: string,
		value: any
	) => boolean;
	get?: (path: string) => any;
	data?: IConfigData;
};