/** Config */
import { config } from '../../config/config';
/** Modules */
import { eventHooks } from '../../modules/event-hooks';
import { log } from '../../modules/logger';
/** Helpers */
import { DOM, getReadableSize, iconElement, dialogs } from '../../helpers';

/** Types */
import { TConfigUpload } from '../../types';

/**
 * One queued file and the row that reports on it
 */
type TQueued = {
	/** Absent for a row that reports something that was never sendable */
	file?: File;
	/**
	 * The directory this file was dropped on, captured then rather than read
	 * at send time: in single-page mode the location can move while a queue is
	 * still running, and the rest of the batch would follow it
	 */
	target?: string;
	row: HTMLElement;
	bar: HTMLElement;
	name: HTMLElement;
	status: HTMLElement;
};

/** Where the live marker is kept, see `claim()` */
const LIVE_KEY = '__ivfiUploadLive';

interface IUploadWindow extends Window {
	__ivfiUploadLive?: object;
}

/**
 * Drag and drop uploads into the directory currently being listed.
 *
 * Every check made here is repeated server-side. This exists so that a file
 * the server would refuse is refused before it is sent rather than after, not
 * as the thing standing between the directory and an unwanted file
 */
class componentUpload
{
	private settings: TConfigUpload;

	/** Depth of nested dragenter/dragleave pairs, see `isDragged()` */
	private depth = 0;

	private overlay: HTMLElement = null;

	private panel: HTMLElement = null;

	private list: HTMLElement = null;

	private close: HTMLElement = null;

	private announcer: HTMLElement = null;

	private queue: Array<TQueued> = [];

	private busy = false;

	/** Whether anything landed, so the listing is known to be out of date */
	private wrote = false;

	/** The directory the current batch was dropped on */
	private target: string = null;

	/** The request in flight, so that closing can stop it */
	private request: XMLHttpRequest = null;

	constructor()
	{
		this.settings = config.get('upload') || {};

		/**
		 * Claimed before the check, not after it. Navigating from a page that
		 * accepts uploads to one that does not builds a component that returns
		 * here, and leaving the marker alone would leave the previous page's
		 * instance live: it would still answer drops, and still show a drop
		 * target, on a page that has no upload to offer
		 */
		this.claim();

		if(!this.settings.enabled)
		{
			return this;
		}

		this.bind();

		if(this.settings.directories)
		{
			this.createDirectoryButton();
		}

		if(this.settings.delete)
		{
			this.createDeleteButtons();
		}

		return this;
	}

	/**
	 * Adds a delete control to every file and folder row.
	 *
	 * Added to the rows the page arrived with, which sorting and filtering move
	 * and hide but never rebuild, so each control stays with its row. One
	 * listener on the table answers all of them
	 */
	private createDeleteButtons = (): void =>
	{
		const table = document.body.querySelector(':scope > div.tableContainer > table');

		if(!table)
		{
			return;
		}

		/* Widens the last column, which otherwise has no room beside [Download] */
		table.classList.add('deletable');

		table.querySelectorAll(':scope > tbody > tr.file, :scope > tbody > tr.directory').forEach((row: Element) =>
		{
			const name = row.children[0] ? row.children[0].getAttribute('data-raw') : null;
			const cell = row.lastElementChild;

			if(name === null || !cell)
			{
				return;
			}

			const button = DOM.new('button', {
				class : 'deleteItem',
				type : 'button',
				title : `Delete ${name}`,
				'aria-label' : `Delete ${name}`
			});

			button.append(iconElement('trash-2'));
			cell.append(button);
		});

		eventHooks.listen(table as HTMLElement, 'click', 'uploadDeleteItem', (event: MouseEvent) =>
		{
			const button = (event.target as HTMLElement).closest('button.deleteItem');

			if(!button)
			{
				return;
			}

			event.preventDefault();

			const row = button.closest('tr');

			this.deleteItem(
				row.children[0].getAttribute('data-raw'),
				row.classList.contains('directory')
			);
		});
	};

	/**
	 * Asks, then deletes one file or empty folder in the listing being viewed
	 */
	private deleteItem = (name: string, directory: boolean): void =>
	{
		dialogs.confirm(directory ? `Delete the folder "${name}"?` : `Delete "${name}"?`, {
			description: directory
				? 'Only an empty folder can be deleted. This cannot be undone.'
				: 'This cannot be undone.',
			confirmLabel: 'Delete',
			destructive: true
		}).then((confirmed) =>
		{
			if(!confirmed)
			{
				return;
			}

			this.post(this.settings.deleteAction, name, directory
				? 'The folder could not be deleted'
				: 'The file could not be deleted');
		});
	};

	/**
	 * Puts folder creation beside the path, where it can be seen.
	 *
	 * The menu item alone left it behind the gear, which nothing points at. It
	 * goes in before the path rather than inside it: floated there, the path
	 * (which hides its overflow) narrows to sit beside it and still ends a long
	 * path in an ellipsis, where a float inside it would have the path run
	 * underneath the button instead
	 */
	private createDirectoryButton = (): void =>
	{
		const path = document.body.querySelector(':scope > div.path');

		if(!path)
		{
			return;
		}

		const button = DOM.new('button', {
			class : 'newFolder',
			type : 'button',
			text : 'New folder'
		});

		button.prepend(iconElement('folder-plus'));
		path.before(button);

		eventHooks.listen(button, 'click', 'uploadNewFolder', () => this.createDirectory());
	};

	/**
	 * Marks this instance as the one drops belong to.
	 *
	 * Single-page navigation replaces the document with `document.write()` and
	 * builds another component, but `window` and the listeners on it survive
	 * that. Every instance an earlier navigation left behind would otherwise
	 * handle the same drop, and one file would be uploaded once per page the
	 * client has visited.
	 *
	 * The marker lives on `window` rather than in this module, because the
	 * replacement document loads `main.js` again and that runs in a module
	 * scope of its own: a variable here is one per bundle execution, so every
	 * older bundle would still read itself as the live one
	 */
	private claim = (): void =>
	{
		(window as IUploadWindow)[LIVE_KEY] = this;
	};

	/**
	 * Whether this instance is the one the current document built
	 */
	private isLive = (): boolean =>
	{
		return (window as IUploadWindow)[LIVE_KEY] === this;
	};

	/**
	 * Whether a drag is carrying files.
	 *
	 * Dragging selected text or a link also fires these events, and answering
	 * those with a full-screen drop target would take over the page for a
	 * gesture that has nothing to do with uploading
	 */
	private isDragged = (event: DragEvent): boolean =>
	{
		if(!this.isLive())
		{
			return false;
		}

		const types = event.dataTransfer ? event.dataTransfer.types : null;

		if(!types)
		{
			return false;
		}

		return Array.from(types).includes('Files');
	};

	private bind = (): void =>
	{
		/**
		 * Without cancelling these two the browser navigates away to the
		 * dropped file, which is the default for a document that does not
		 * handle the drop itself
		 */
		eventHooks.listen(window, 'dragover', 'uploadDragOver', (event: DragEvent) =>
		{
			if(this.isDragged(event))
			{
				event.preventDefault();
			}
		});

		eventHooks.listen(window, 'dragenter', 'uploadDragEnter', (event: DragEvent) =>
		{
			if(!this.isDragged(event))
			{
				return;
			}

			event.preventDefault();

			/**
			 * `dragenter` and `dragleave` fire again for every element the
			 * pointer crosses on the way in, so the overlay is tied to a depth
			 * count rather than to the events themselves. Tracking the events
			 * alone makes it flicker over every child it passes
			 */
			this.depth++;

			this.showOverlay();
		});

		eventHooks.listen(window, 'dragleave', 'uploadDragLeave', (event: DragEvent) =>
		{
			if(!this.isDragged(event))
			{
				return;
			}

			this.depth = Math.max(0, this.depth - 1);

			if(this.depth === 0)
			{
				this.hideOverlay();
			}
		});

		/**
		 * A drag that ends outside the window does not always produce the
		 * matching `dragleave`, which would leave the overlay covering a page
		 * nothing was dropped on
		 */
		eventHooks.listen(window, 'dragend', 'uploadDragEnd', () =>
		{
			if(!this.isLive())
			{
				return;
			}

			this.depth = 0;
			this.hideOverlay();
		});

		eventHooks.listen(window, 'drop', 'uploadDrop', (event: DragEvent) =>
		{
			if(!this.isDragged(event))
			{
				return;
			}

			event.preventDefault();

			this.depth = 0;
			this.hideOverlay();

			this.accept(event.dataTransfer);
		});
	};

	/**
	 * Creates the overlay shown while files are over the window
	 */
	private showOverlay = (): void =>
	{
		if(this.overlay)
		{
			return;
		}

		const inner = DOM.new('div', {
			class : 'uploadDropInner',
			text : 'Drop files to upload'
		});

		inner.prepend(iconElement('upload'));

		this.overlay = DOM.new('div', {
			class : 'uploadDrop'
		});

		this.overlay.append(inner);
		document.body.append(this.overlay);

		/* Applied on the next frame so the transition has a state to leave */
		requestAnimationFrame(() =>
		{
			if(this.overlay)
			{
				this.overlay.classList.add('visible');
			}
		});
	};

	private hideOverlay = (): void =>
	{
		if(!this.overlay)
		{
			return;
		}

		this.overlay.remove();
		this.overlay = null;
	};

	/**
	 * A byte count in a form that says something.
	 *
	 * `getReadableSize()` starts at KiB, so anything under about half of one
	 * rounds to `0 KiB` — including the deliberate one-byte floor, which would
	 * have the client refusing files for being "larger than the 0 KiB limit"
	 */
	private readable = (bytes: number): string =>
	{
		const sizes = config.get('format').sizes;

		if(bytes < 1024)
		{
			return `${bytes}${sizes[0]}`.trim();
		}

		return getReadableSize(sizes, bytes).trim();
	};

	/**
	 * The name the endpoint would write, as far as the parts that decide
	 * whether it is accepted.
	 *
	 * Kept in step with `uploadSafeName()`. Deriving the extension from the raw
	 * name instead turns `holiday.jpg.` into an empty extension here while the
	 * server trims the dot and accepts it, which refuses a file the endpoint
	 * would have taken
	 */
	private sanitised = (name: string): string =>
	{
		const separator = Math.max(name.lastIndexOf('/'), name.lastIndexOf('\\'));
		const base = separator === -1 ? name : name.slice(separator + 1);

		/**
		 * Space, tab and dot, which is what `rtrim()`/`ltrim()` are given
		 * server-side. `\s` would be wrong here: it covers Unicode whitespace
		 * the server keeps, so a name ending in a non-breaking space would look
		 * like a plain `.jpg` to this check and reach the endpoint as one
		 * ending in `jpg\u00a0`, which no allowlist has
		 */
		/* eslint-disable-next-line no-control-regex */
		return base.replace(/[\x00-\x1F\x7F]+/g, '')
			.replace(/^[ \t.]+/, '')
			.replace(/[ \t.]+$/, '');
	};

	/**
	 * Why a file cannot be sent, or null when it can
	 *
	 * Only refuses what the server refuses. A rule that lives here alone would
	 * turn away a file the endpoint accepts, which is a worse failure than not
	 * checking at all: it cannot be worked around, and nothing explains it
	 */
	private rejection = (file: File): string =>
	{
		const extensions = this.settings.extensions || [];
		const maximum = this.settings.maxSize || 0;

		const parts = this.sanitised(file.name).toLowerCase().split('.');

		if(parts.length < 2)
		{
			return 'No file extension';
		}

		const extension = parts[parts.length - 1];

		if(!extensions.includes(extension))
		{
			return `.${extension} is not accepted here`;
		}

		/**
		 * The endpoint refuses a blocked extension anywhere in the name, not
		 * only at the end, because Apache can hand `payload.php.jpg` to PHP.
		 * Checked here too, so such a drop is named rather than uploaded in
		 * full and then answered with a 415
		 */
		const blocked = this.settings.blocked || [];

		if(parts.slice(1, -1).some((part: string) => blocked.includes(part)))
		{
			/* Not "may execute": the same list covers `.svg` and friends */
			return 'That name carries an extension that is not accepted here';
		}

		if(maximum > 0 && file.size > maximum)
		{
			return `Larger than the ${this.readable(maximum)} limit`;
		}

		return null;
	};

	/**
	 * The names of the dropped entries that are directories.
	 *
	 * Read from `items` rather than inferred from `files`, where a directory
	 * is indistinguishable from an empty file. It has to happen inside the drop
	 * handler, because the list does not survive past it
	 */
	private droppedDirectories = (transfer: DataTransfer): Array<string> =>
	{
		const items = transfer && transfer.items ? Array.from(transfer.items) : [];
		const names: Array<string> = [];

		items.forEach((item: DataTransferItem) =>
		{
			if(item.kind !== 'file' || typeof item.webkitGetAsEntry !== 'function')
			{
				return;
			}

			const entry = item.webkitGetAsEntry();

			if(entry && entry.isDirectory)
			{
				names.push(entry.name);
			}
		});

		return names;
	};

	/**
	 * Queues whatever was dropped
	 */
	private accept = (transfer: DataTransfer): void =>
	{
		const files = transfer && transfer.files ? Array.from(transfer.files) : [];
		const directories = this.droppedDirectories(transfer);

		if(files.length === 0 && directories.length === 0)
		{
			return;
		}

		/* Where these files are bound for, fixed at the moment they were dropped */
		const target = window.location.pathname;

		this.target = target;

		this.createPanel();
		this.updateClose();

		/**
		 * Named rather than skipped. A dropped folder otherwise produces either
		 * nothing at all or a zero byte entry, and both read as the drop having
		 * been ignored
		 */
		directories.forEach((name: string) =>
		{
			this.settle(
				this.createRow(name), 'failed', 'Folders are not accepted'
			);
		});

		files.forEach((file: File) =>
		{
			/* A folder also reaches `files`, as an entry there is no point sending */
			if(directories.includes(file.name))
			{
				return;
			}

			const queued = this.createRow(file.name, file);
			const rejection = this.rejection(file);

			queued.target = target;

			if(rejection !== null)
			{
				this.settle(queued, 'failed', rejection);

				return;
			}

			this.queue.push(queued);
		});

		this.next();
	};

	/**
	 * Asks for a name and creates a directory in the listing being viewed.
	 *
	 * Public, because the menu item is what reaches it: this component owns the
	 * write endpoint and its token, and a second place assembling that request
	 * would be a second place to keep in step with the server
	 */
	public createDirectory = (): void =>
	{
		if(!this.settings.enabled || !this.settings.directories)
		{
			return;
		}

		dialogs.prompt('New folder', {
			label: 'Folder name',
			placeholder: 'Folder name',
			confirmLabel: 'Create'
		}).then((name) =>
		{
			/* Cancelled, rather than confirmed with nothing in it */
			if(name === null)
			{
				return;
			}

			if(name.trim() === '')
			{
				dialogs.alert('A folder needs a name');

				return;
			}

			this.post(this.settings.directoryAction, name, 'The folder could not be created');
		});
	};

	/**
	 * Sends a named action to the directory being viewed, reloading the listing
	 * when it lands and saying why when it does not.
	 *
	 * Folder creation and deletion are the same request apart from the action,
	 * so they share it rather than keeping two copies in step with the server
	 */
	private post = (action: string, name: string, failure: string): void =>
	{
		const fields = this.settings.fields || {};
		const body = new URLSearchParams();

		body.append(fields.action, action);
		body.append(fields.token, this.settings.token);
		body.append(fields.name, name);

		fetch(window.location.pathname, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Accept': 'application/json'
			},
			body: body.toString()
		}).then((response) => response.json().then((payload) => ({
			ok: response.ok, payload
		}))).then(({ ok, payload }) =>
		{
			if(ok && payload && payload.ok)
			{
				/* The listing is out of date, so it is fetched again */
				window.location.reload();

				return;
			}

			dialogs.alert(failure, payload && payload.error ? payload.error : undefined);
		}).catch((error) =>
		{
			log('upload', error);

			/**
			 * Anything but the JSON this endpoint answers with means the request
			 * never reached it, a session that expired being the likeliest
			 */
			dialogs.alert(failure, 'Reload the page and try again.');
		});
	};

	/**
	 * Creates the panel the queue is reported in
	 */
	private createPanel = (): void =>
	{
		if(this.panel)
		{
			return;
		}

		/**
		 * A button rather than a styled div, so it takes focus and answers the
		 * keyboard without any of that having to be reimplemented here
		 */
		const close = DOM.new('button', {
			class : 'uploadClose',
			type : 'button',
			title : 'Close',
			'aria-label' : 'Close the upload queue'
		});

		close.append(iconElement('x'));

		this.close = close;

		const header = DOM.new('div', {
			class : 'uploadHeader'
		});

		header.append(DOM.new('div', {
			class : 'uploadTitle',
			text : 'Uploads'
		}), close);

		this.list = DOM.new('div', {
			class : 'uploadList'
		});

		/**
		 * Only outcomes are announced, not progress. A live region carrying the
		 * percentages would read every one of them out, which buries the thing
		 * the listener actually needs in a count from 0 to 100
		 */
		this.announcer = DOM.new('div', {
			class : 'uploadAnnouncer',
			role : 'status',
			'aria-live' : 'polite'
		});

		this.panel = DOM.new('div', {
			class : 'uploadPanel'
		});

		this.panel.append(header, this.list, this.announcer);
		document.body.append(this.panel);

		eventHooks.listen(close, 'click', 'uploadClose', () => this.dismiss());
	};

	/**
	 * Closes the panel, refreshing first if anything was written
	 */
	private dismiss = (): void =>
	{
		/**
		 * Reloading mid-queue would abort the request in flight and drop
		 * whatever is still waiting, so closing asks first rather than doing it
		 * silently.
		 *
		 * Asking rather than refusing, because a request can stall without ever
		 * completing: `XMLHttpRequest` has no timeout by default, and its only
		 * endings here are load, error and abort. A button that simply stayed
		 * disabled would leave the queue wedged with nothing the client could
		 * do about it
		 */
		if(this.busy || this.queue.length > 0)
		{
			dialogs.confirm('Stop uploading?', {
				description: 'Uploads are still running. Anything not yet sent will be skipped.',
				confirmLabel: 'Stop uploads',
				destructive: true
			}).then((confirmed) =>
			{
				if(!confirmed)
				{
					return;
				}

				this.queue = [];

				if(this.request)
				{
					/* Settles the current row and drains what is left of the queue */
					this.request.abort();
				}
			});

			return;
		}

		if(this.wrote && (this.target === null
			|| this.target === window.location.pathname))
		{
			window.location.reload();

			return;
		}

		if(this.panel)
		{
			this.panel.remove();
			this.panel = null;
			this.list = null;
			this.close = null;
			this.announcer = null;
		}
	};

	/**
	 * Says what closing would do, without ever taking the option away
	 */
	private updateClose = (): void =>
	{
		if(!this.close)
		{
			return;
		}

		const running = this.busy || this.queue.length > 0;

		this.close.setAttribute(
			'title', running ? 'Stop uploading and close' : 'Close'
		);
		this.close.setAttribute(
			'aria-label',
			running ? 'Stop uploading and close the upload queue' : 'Close the upload queue'
		);
	};

	private createRow = (label: string, file?: File): TQueued =>
	{
		const bar = DOM.new('div', {
			class : 'uploadBarFill'
		});

		const status = DOM.new('div', {
			class : 'uploadStatus',
			text : 'Waiting'
		});

		const name = DOM.new('div', {
			class : 'uploadName',
			title : label,
			text : label
		});

		const meta = DOM.new('div', {
			class : 'uploadMeta'
		});

		meta.append(name, status);

		const track = DOM.new('div', {
			class : 'uploadBar'
		});

		track.append(bar);

		const row = DOM.new('div', {
			class : 'uploadItem'
		});

		row.append(meta, track);
		this.list.append(row);

		return { file, row, bar, name, status };
	};

	/**
	 * Renames a row to whatever the server says it wrote.
	 *
	 * The endpoint sanitises the name it is given, so the row can otherwise be
	 * left naming a file that is not on disk under that name
	 */
	private rename = (queued: TQueued, written: string): void =>
	{
		if(!written || written === queued.name.textContent)
		{
			return;
		}

		queued.name.textContent = written;
		queued.name.setAttribute('title', written);
	};

	/**
	 * Marks a row as done, one way or the other
	 */
	private settle = (queued: TQueued, state: string, message: string): void =>
	{
		queued.row.classList.add(state);
		queued.status.textContent = message;
		queued.status.setAttribute('title', message);

		DOM.style.set(queued.bar, {
			width : '100%'
		});

		/* The row changing colour is the whole outcome otherwise */
		if(this.announcer)
		{
			this.announcer.textContent = `${queued.name.textContent}: ${
				state === 'done' ? 'uploaded' : message
			}`;
		}
	};

	/**
	 * Sends the next queued file, one at a time.
	 *
	 * Sequential rather than parallel because each file is its own request:
	 * several at once would compete for the same upstream bandwidth, report
	 * progress that means nothing individually, and multiply what a single
	 * `max_file_uploads` or connection limit turns away
	 */
	private next = (): void =>
	{
		if(this.busy)
		{
			return;
		}

		const queued = this.queue.shift();

		if(!queued)
		{
			this.updateClose();

			/* The queue has drained: show the listing the uploads are missing from */
			if(this.wrote && this.settled())
			{
				setTimeout(() => this.refresh(), 600);
			}

			return;
		}

		this.busy = true;
		this.updateClose();

		this.send(queued, () =>
		{
			this.busy = false;
			this.next();
		});
	};

	/**
	 * Reloads once the queue really is finished with.
	 *
	 * Checked again rather than trusted from when the timer was set: a drop
	 * arriving inside the delay starts the queue again, and reloading over it
	 * aborts the request in flight and hides whatever it was about to report
	 */
	private refresh = (): void =>
	{
		/**
		 * A navigation away and back inside the delay would otherwise let an
		 * instance from the old document reload the page the new one is on,
		 * taking its queue with it
		 */
		if(!this.isLive())
		{
			return;
		}

		if(this.busy || this.queue.length > 0 || !this.settled())
		{
			return;
		}

		/**
		 * In single-page mode the listing can have moved on since the drop. The
		 * files went where they were dropped, so there is nothing here to show
		 */
		if(this.target !== null && this.target !== window.location.pathname)
		{
			return;
		}

		window.location.reload();
	};

	/**
	 * Whether every row finished without failing
	 */
	private settled = (): boolean =>
	{
		return this.list
			? this.list.querySelectorAll(':scope > .uploadItem.failed').length === 0
			: true;
	};

	private send = (queued: TQueued, done: () => void): void =>
	{
		const fields = this.settings.fields || {};
		const body = new FormData();

		body.append(fields.action, this.settings.action);
		body.append(fields.token, this.settings.token);
		body.append(fields.file, queued.file, queued.file.name);

		const request = new XMLHttpRequest();

		this.request = request;

		queued.row.classList.add('active');
		queued.status.textContent = '0%';

		request.upload.addEventListener('progress', (event: ProgressEvent) =>
		{
			if(!event.lengthComputable)
			{
				return;
			}

			const percentage = Math.round((event.loaded / event.total) * 100);

			DOM.style.set(queued.bar, {
				width : `${percentage}%`
			});

			queued.status.textContent = `${percentage}%`;
		});

		request.addEventListener('load', () =>
		{
			this.request = null;

			queued.row.classList.remove('active');

			let payload: {
				ok?: boolean;
				error?: string;
				file?: {
					name?: string;
					size?: number;
				};
			} = null;

			/**
			 * Anything but the JSON this endpoint answers with means the
			 * request never reached it: a session that expired renders the
			 * login page, and a proxy in front can answer with its own error
			 */
			try
			{
				payload = JSON.parse(request.responseText);
			} catch(error)
			{
				log('upload', error);
			}

			if(request.status === 201 && payload && payload.ok)
			{
				this.wrote = true;

				/* The endpoint sanitises names, so the row follows what it wrote */
				this.rename(queued, payload.file ? payload.file.name : null);

				this.settle(queued, 'done', this.readable(queued.file.size));
			} else if(payload && payload.error)
			{
				this.settle(queued, 'failed', payload.error);
			} else if(request.status === 401 || request.status === 403)
			{
				this.settle(queued, 'failed', 'Not signed in — reload the page');
			} else {
				this.settle(queued, 'failed', `The server answered ${request.status}`);
			}

			done();
		});

		request.addEventListener('error', () =>
		{
			this.request = null;

			queued.row.classList.remove('active');
			this.settle(queued, 'failed', 'The connection failed');

			done();
		});

		request.addEventListener('abort', () =>
		{
			this.request = null;

			queued.row.classList.remove('active');
			this.settle(queued, 'failed', 'Cancelled');

			done();
		});

		/**
		 * Posted back to the directory the file was dropped on, which is the
		 * directory it is written into: the server resolves the target from
		 * the request path it already validated, so there is no path to send
		 */
		request.open('POST', queued.target || window.location.pathname, true);
		request.setRequestHeader('Accept', 'application/json');
		request.send(body);
	};
}

export { componentUpload };
