/** Config */
import { config } from '../../config/config';
/** Modules */
import { eventHooks } from '../../modules/event-hooks';
import { log } from '../../modules/logger';
/** Helpers */
import { DOM, clipboardCopy, dialogs, iconElement } from '../../helpers';

/** Types */
import { HTMLElementEventHooks } from '../../types';
import { TConfigShare, TConfigShareEntry } from '../../types';

/**
 * What the share endpoint answers with.
 *
 * `ok` says whether it acted; `shares` is the whole live set afterwards, so
 * the panel redraws from the server's view rather than from its own guess
 */
type TSharePayload = {
	ok?: boolean;
	error?: string;
	share?: TConfigShareEntry;
	shares?: Array<TConfigShareEntry>;
};

/**
 * The lifetimes offered when minting a link.
 *
 * `0` is a link that does not expire, which is what the server stores when it
 * is asked for nothing
 */
const LIFETIMES: Array<{ label: string; seconds: number }> = [
	{ label: 'Never', seconds: 0 },
	{ label: 'After a day', seconds: 86400 },
	{ label: 'After a week', seconds: 604800 },
	{ label: 'After 30 days', seconds: 2592000 }
];

/**
 * Read-only links to a directory, for someone who does not have the password.
 *
 * The link is the whole of the secret, so it is only ever assembled here, from
 * this page's own origin: the server describes a share by path and token and
 * never has to decide what it is reachable as. Minting and revoking are the
 * server's to allow, and it checks the session again on both.
 */
class componentShare
{
	private settings: TConfigShare;

	/** The shares the page arrived with, replaced by whatever a write returns */
	private shares: Array<TConfigShareEntry> = [];

	private container: HTMLElement = null;

	private list: HTMLElement = null;

	/** Listeners this panel added, so closing it takes them all back out */
	private boundEvents: {
		[id: string]: {
			selector: any;
			events: Array<string>;
		};
	} = {};

	constructor()
	{
		this.settings = config.get('share') || {};

		if(this.settings.enabled)
		{
			this.shares = this.settings.shares || [];

			this.createShareButton();
		}

		return this;
	}

	/**
	 * Puts sharing beside the path, next to folder creation.
	 *
	 * Behind the menu it was easy to miss, which is the same reason the New
	 * folder button is there. It goes in after that one, so a float sends it
	 * to the left of it and the button already in the corner does not move,
	 * and before the path for the reason that one explains: floated there, the
	 * path narrows to sit beside it rather than running underneath
	 */
	private createShareButton = (): void =>
	{
		const path = document.body.querySelector(':scope > div.path');

		if(!path || document.body.querySelector(':scope > button.shareFolder'))
		{
			return;
		}

		const button = DOM.new('button', {
			class : 'shareFolder',
			type : 'button',
			text : 'Share'
		});

		button.prepend(iconElement('link'));
		path.before(button);

		/**
		 * Kept out of `boundEvents`, which `close()` empties: that is for the
		 * panel's own listeners, and this one has to outlive the panel being
		 * opened and closed
		 */
		eventHooks.listen(button, 'click', 'shareFolderButton', () => this.show());
	};

	/**
	 * A configured lifetime in words, for the option that names it
	 */
	private describeSeconds = (seconds: number): string =>
	{
		const units: Array<[string, number]> = [
			['day', 86400], ['hour', 3600], ['minute', 60]
		];

		for(const [name, size] of units)
		{
			if(seconds >= size)
			{
				const count = Math.round(seconds / size);

				return `${count} ${name}${count === 1 ? '' : 's'}`;
			}
		}

		return `${seconds} seconds`;
	};

	/**
	 * Whether the menu should offer this at all
	 */
	public available = (): boolean =>
	{
		return this.settings.enabled === true;
	};

	/**
	 * The link a recipient follows.
	 *
	 * Built from `window.location.origin` rather than anything the server
	 * said, so the document never has to trust the `Host` header to describe
	 * where it is. Each path segment is encoded, since a directory name may
	 * hold anything a filesystem allows
	 */
	private linkFor = (share: TConfigShareEntry): string =>
	{
		/**
		 * `url` is the server's own public form of the path: encoded, and
		 * carrying the prepend where the script is reached through a proxy
		 * mount, so the link lands on the same route the listing's own links
		 * use. Only the origin comes from here, so the document never has to
		 * trust the `Host` header to say where it is
		 */
		return `${window.location.origin}${share.url}`
			+ `?${this.settings.param}=${encodeURIComponent(share.token)}`;
	};

	/**
	 * Closes the panel, leaving the page as it was
	 */
	public close = (): void =>
	{
		Object.keys(this.boundEvents).forEach((eventId: string) =>
		{
			const { selector, events } = this.boundEvents[eventId];

			eventHooks.unlisten(selector, events, eventId);
		});

		this.boundEvents = {};

		document.body.querySelectorAll(
			':scope > div.focusOverlay, :scope > div.shareContainer'
		).forEach((element) => element.remove());

		this.container = null;
		this.list = null;
	};

	/**
	 * Records a listener against the panel, so `close()` can undo it
	 */
	private removeOnUnbind = (
		element: HTMLElementEventHooks,
		events: Array<string>,
		id: string
	): void =>
	{
		this.boundEvents[id] = {
			selector: element, events
		};
	};

	/**
	 * Opens the panel
	 */
	public show = (): void =>
	{
		if(!this.available() || this.container)
		{
			return;
		}

		if(!document.body.querySelector(':scope > div.focusOverlay'))
		{
			const overlay = DOM.new('div', {
				class : 'focusOverlay'
			});

			document.body.appendChild(overlay);

			eventHooks.listen(overlay, 'click', 'shareOverlayClick', () => this.close(), {
				onAdd: this.removeOnUnbind
			});
		}

		this.container = DOM.new('div', {
			class : 'shareContainer'
		});

		const wrapper = DOM.new('div', {
			class : 'wrapper'
		});

		wrapper.append(DOM.new('div', {
			class : 'title',
			text : 'Share a folder'
		}));

		wrapper.append(this.createSection());

		this.list = DOM.new('div', {
			class : 'shareList'
		});

		wrapper.append(this.list);

		this.renderList();

		const bottom = DOM.new('div', {
			class : 'bottom'
		});

		const done = DOM.new('button', {
			type : 'button',
			class : 'cancel ns',
			text : 'Done'
		});

		bottom.append(done);

		eventHooks.listen(done, 'click', 'shareCloseClick', () => this.close(), {
			onAdd: this.removeOnUnbind
		});

		this.container.append(wrapper, bottom);

		document.body.appendChild(this.container);
	};

	/**
	 * The part of the panel that mints a new link
	 */
	private createSection = (): HTMLElement =>
	{
		const section = DOM.new('div', {
			class : 'shareCreate'
		});

		/**
		 * The root is refused by the server, because a link to it would hand
		 * over every directory at once. Said here rather than offering a
		 * control that answers with a refusal
		 */
		if(!this.settings.shareable)
		{
			section.append(DOM.new('p', {
				class : 'note',
				text : 'Open a folder to share it. The whole index cannot be shared at once.'
			}));

			return section;
		}

		section.append(DOM.new('p', {
			class : 'note',
			text : 'Anyone with the link can browse this folder and what is '
				+ 'inside it. They cannot upload, rename or delete.'
		}));

		const row = DOM.new('div', {
			class : 'row'
		});

		const label = DOM.new('label', {
			for : 'shareExpiry',
			text : 'Expires'
		});

		const select = DOM.new('select', {
			id : 'shareExpiry'
		}) as HTMLSelectElement;

		const configured = this.settings.expiry || 0;

		/**
		 * A configured default that is not one of the presets would otherwise
		 * leave the list on `Never`, and creating a link would post 0 and
		 * quietly override the server's own default
		 */
		const lifetimes = LIFETIMES.some((item) => item.seconds === configured)
			? LIFETIMES
			: LIFETIMES.concat([{
				label: `After ${this.describeSeconds(configured)}`,
				seconds: configured
			}]).sort((a, b) => a.seconds - b.seconds);

		lifetimes.forEach((lifetime) =>
		{
			const option = DOM.new('option', {
				value : `${lifetime.seconds}`,
				text : lifetime.label
			}) as HTMLOptionElement;

			/* The configured default, so the usual choice is already made */
			if(lifetime.seconds === configured)
			{
				option.selected = true;
			}

			select.append(option);
		});

		const button = DOM.new('button', {
			type : 'button',
			class : 'create ns',
			text : 'Create link'
		});

		eventHooks.listen(button, 'click', 'shareCreateClick', () =>
		{
			this.create(parseInt(select.value, 10) || 0);
		}, {
			onAdd: this.removeOnUnbind
		});

		row.append(label, select, button);
		section.append(row);

		return section;
	};

	/**
	 * Takes back the listeners belonging to the rows currently drawn
	 */
	private releaseRowListeners = (): void =>
	{
		Object.keys(this.boundEvents).forEach((eventId: string) =>
		{
			if(!eventId.startsWith('shareCopyClick_')
				&& !eventId.startsWith('shareRevokeClick_'))
			{
				return;
			}

			const { selector, events } = this.boundEvents[eventId];

			eventHooks.unlisten(selector, events, eventId);

			delete this.boundEvents[eventId];
		});
	};

	/**
	 * Draws the live links, the ones for this folder first
	 */
	private renderList = (): void =>
	{
		if(!this.list)
		{
			return;
		}

		/**
		 * The rows are rebuilt on every create and revoke, and their listener
		 * ids are positional, so a redraw would register new ones over the top
		 * of the old entries in `boundEvents`. The elements they were bound to
		 * are about to be thrown away, and nothing would be left that could
		 * take them back out again, so they go first
		 */
		this.releaseRowListeners();

		this.list.textContent = '';

		if(this.shares.length === 0)
		{
			this.list.append(DOM.new('p', {
				class : 'note',
				text : 'No links yet.'
			}));

			return;
		}

		const here = window.location.pathname;

		/**
		 * This folder's links are what someone opening the panel came for, so
		 * they are shown first. The rest are still listed, because a link that
		 * cannot be found is a link that cannot be taken back
		 */
		const ordered = this.shares.slice().sort((a, b) =>
		{
			const score = (share: TConfigShareEntry): number =>
			{
				return share.path === here ? 0 : 1;
			};

			return score(a) - score(b) || b.created - a.created;
		});

		ordered.forEach((share, index) =>
		{
			this.list.append(this.createRow(share, index, share.path === here));
		});
	};

	/**
	 * One live link
	 */
	private createRow = (
		share: TConfigShareEntry,
		index: number,
		isHere: boolean
	): HTMLElement =>
	{
		const row = DOM.new('div', {
			class : `shareRow${isHere ? ' here' : ''}`
		});

		const details = DOM.new('div', {
			class : 'details'
		});

		details.append(DOM.new('div', {
			class : 'path',
			text : share.path
		}));

		details.append(DOM.new('div', {
			class : 'meta',
			text : share.expires
				? `Expires ${new Date(share.expires * 1000).toLocaleDateString()}`
				: 'Does not expire'
		}));

		const actions = DOM.new('div', {
			class : 'actions'
		});

		const copy = DOM.new('button', {
			type : 'button',
			class : 'copy ns',
			title : 'Copy link',
			'aria-label' : 'Copy link'
		});

		copy.append(iconElement('copy'));

		const revoke = DOM.new('button', {
			type : 'button',
			class : 'revoke ns',
			title : 'Revoke link',
			'aria-label' : 'Revoke link'
		});

		revoke.append(iconElement('trash-2'));

		eventHooks.listen(copy, 'click', `shareCopyClick_${index}`, () =>
		{
			clipboardCopy(this.linkFor(share));

			copy.setAttribute('copied', '');

			window.setTimeout(() => copy.removeAttribute('copied'), 1200);
		}, {
			onAdd: this.removeOnUnbind
		});

		eventHooks.listen(revoke, 'click', `shareRevokeClick_${index}`, () =>
		{
			dialogs.confirm(`Revoke the link to "${share.path}"?`, {
				description: 'Anyone holding it will be asked for a password instead.',
				confirmLabel: 'Revoke',
				destructive: true
			}).then((confirmed) =>
			{
				if(confirmed)
				{
					this.revoke(share.token);
				}
			});
		}, {
			onAdd: this.removeOnUnbind
		});

		actions.append(copy, revoke);
		row.append(details, actions);

		return row;
	};

	/**
	 * Asks the server for a new link to the directory being viewed
	 */
	private create = (lifetime: number): void =>
	{
		const fields = this.settings.fields || {};
		const body = new URLSearchParams();

		body.append(fields.action, this.settings.createAction);
		body.append(fields.token, this.settings.token);
		body.append(fields.expiry, `${lifetime}`);

		this.post(body, 'The link could not be created').then((payload) =>
		{
			if(!payload)
			{
				return;
			}

			this.shares = payload.shares || [];

			this.renderList();

			/**
			 * Copied as soon as it exists, because that is the only reason to
			 * have asked for one. It stays in the list either way
			 */
			if(payload.share)
			{
				clipboardCopy(this.linkFor(payload.share));

				dialogs.alert(
					'Link copied',
					'Anyone with it can browse this folder, read-only.'
				);
			}
		});
	};

	/**
	 * Asks the server to drop a link
	 */
	private revoke = (token: string): void =>
	{
		const fields = this.settings.fields || {};
		const body = new URLSearchParams();

		body.append(fields.action, this.settings.revokeAction);
		body.append(fields.token, this.settings.token);
		body.append(fields.share, token);

		this.post(body, 'The link could not be revoked').then((payload) =>
		{
			if(!payload)
			{
				return;
			}

			this.shares = payload.shares || [];

			this.renderList();
		});
	};

	/**
	 * Sends a share request, answering with its payload or with null once it
	 * has said why it failed
	 */
	private post = (
		body: URLSearchParams,
		failure: string
	): Promise<TSharePayload | null> =>
	{
		return fetch(window.location.pathname, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Accept': 'application/json'
			},
			body: body.toString()
		}).then((response) => response.json().then((payload: TSharePayload) => ({
			ok: response.ok, payload
		}))).then(({ ok, payload }): TSharePayload | null =>
		{
			if(ok && payload && payload.ok)
			{
				return payload;
			}

			dialogs.alert(failure, payload && payload.error ? payload.error : undefined);

			return null;
		}).catch((error): null =>
		{
			log('share', error);

			/**
			 * Anything but the JSON this endpoint answers with means the
			 * request never reached it, a session that expired being the
			 * likeliest
			 */
			dialogs.alert(failure, 'Reload the page and try again.');

			return null;
		});
	};
}

export default componentShare;

export { componentShare };
