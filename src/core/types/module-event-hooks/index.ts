import {
	IWindowGlobals,
	IDocumentGlobals
} from '../common';

/**
 * A registered listener, per DOM element, keyed by event name then by the
 * ID it was `listen()`ed under
 */
export type TElementEventHooksEvents = {
	[eventName: string]: {
		[id: string]: IEventItem;
	};
};

/** Whether an element already has a single, shared DOM listener attached
 * for a given event name */
export type TElementEventHooksCallback = {
	[eventName: string]: boolean;
};

export interface IElementEventHooks {
	events: TElementEventHooksEvents;
	hasCallback: TElementEventHooksCallback;
}

export interface HTMLElementEventHooks extends HTMLElement
{
	uniqueHookId: number;
	eventHooks: IElementEventHooks;
}

export interface EventTargetEventHooks extends EventTarget
{
	uniqueHookId?: number;
	eventHooks?: IElementEventHooks | null,
	tagName?: string
}

/** The callbacks registered for one event, under one `listen()` ID and one
 * per-element unique ID */
export interface IEventCallbackEntry {
	callbacks: Array<(...args: any) => void>;
}

/**
 * The module-level listener registry: `listen()` ID, then a per-element
 * unique ID, then event name
 */
export type TEventHooksEvents = {
	[id: string]: {
		[uniqueId: string]: {
			[eventName: string]: IEventCallbackEntry;
		};
	};
};

/** Self-defined event subscriptions, keyed by event name then by ID */
export type TEventHooksSubs = {
	[eventName: string]: {
		[id: string]: (...args: any) => void;
	};
};

export interface IEventHooks {
	events: TEventHooksEvents;
	subs: TEventHooksSubs;
	currentId: number;

	listenSetState?: (
		selector: IWindowGlobals
			| IDocumentGlobals
			| HTMLElement
			| HTMLElementEventHooks
			| string,
		events: Array<string> | string,
		id: any,
		state: boolean
	) => void;

	unlisten?: (
		selector: IWindowGlobals
			| IDocumentGlobals
			| HTMLElement
			| HTMLElementEventHooks
			| string,
		events: Array<string> | string,
		id: string
	) => boolean | void;

	listen?: (
		selector: IWindowGlobals
			| IDocumentGlobals
			| HTMLElement
			| HTMLElementEventHooks
			| string,
		events: Array<string> | string,
		id: string,
		callback: (...args: any) => void,
		options?: IListenOptions
	) => void;

	subscribe?: (
		event: string,
		id: string,
		callback: (...args: any) => void
	) => void;

	unsubscribe?: (
		event: string,
		id: string
	) => void;

	trigger?: (
		event: string,
		...args: any[]
	) => void;
}

export interface IListenOptions {
	onAdd?: (...args: any) => any;

	options?: object;
	destroy?: boolean;
}

export interface IEventItem {
	active: boolean;
	callbackHandler?: (e: Event) => void | null;
}