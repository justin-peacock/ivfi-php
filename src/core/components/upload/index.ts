/** Config */
import { config } from '../../config/config';
/** Modules */
import { eventHooks } from '../../modules/event-hooks';
import { log } from '../../modules/logger';
/** Helpers */
import { DOM, getReadableSize } from '../../helpers';

/** Types */
import { TConfigUpload } from '../../types';

/** Stylesheets */
import '../../../css/upload.scss';

/**
 * One queued file and the row that reports on it
 */
type TQueued = {
	file: File;
	row: HTMLElement;
	bar: HTMLElement;
	status: HTMLElement;
};

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

	private queue: Array<TQueued> = [];

	private busy = false;

	/** Whether anything landed, so the listing is known to be out of date */
	private wrote = false;

	constructor()
	{
		this.settings = config.get('upload') || {};

		if(!this.settings.enabled)
		{
			return this;
		}

		this.bind();

		return this;
	}

	/**
	 * Whether a drag is carrying files.
	 *
	 * Dragging selected text or a link also fires these events, and answering
	 * those with a full-screen drop target would take over the page for a
	 * gesture that has nothing to do with uploading
	 */
	private isDragged = (event: DragEvent): boolean =>
	{
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
	 * Why a file cannot be sent, or null when it can
	 */
	private rejection = (file: File): string =>
	{
		const extensions = this.settings.extensions || [];
		const maximum = this.settings.maxSize || 0;

		/**
		 * A directory arrives as a zero byte entry with no type, and a folder
		 * drop is common enough to be worth naming rather than reporting as an
		 * empty file the server turned down
		 */
		if(file.size === 0 && file.type === '')
		{
			return 'Folders and empty files are not accepted';
		}

		const parts = file.name.toLowerCase().split('.');

		if(parts.length < 2)
		{
			return 'No file extension';
		}

		if(!extensions.includes(parts[parts.length - 1]))
		{
			return `.${parts[parts.length - 1]} is not accepted here`;
		}

		if(maximum > 0 && file.size > maximum)
		{
			return `Larger than the ${getReadableSize(
				config.get('format').sizes, maximum
			).trim()} limit`;
		}

		return null;
	};

	/**
	 * Queues whatever was dropped
	 */
	private accept = (transfer: DataTransfer): void =>
	{
		const files = transfer && transfer.files ? Array.from(transfer.files) : [];

		if(files.length === 0)
		{
			return;
		}

		this.createPanel();

		files.forEach((file: File) =>
		{
			const queued = this.createRow(file);
			const rejection = this.rejection(file);

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
	 * Creates the panel the queue is reported in
	 */
	private createPanel = (): void =>
	{
		if(this.panel)
		{
			return;
		}

		const close = DOM.new('div', {
			class : 'uploadClose',
			title : 'Close'
		});

		close.innerHTML = '&#10005;';

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

		this.panel = DOM.new('div', {
			class : 'uploadPanel'
		});

		this.panel.append(header, this.list);
		document.body.append(this.panel);

		eventHooks.listen(close, 'click', 'uploadClose', () => this.dismiss());
	};

	/**
	 * Closes the panel, refreshing first if anything was written
	 */
	private dismiss = (): void =>
	{
		if(this.wrote)
		{
			window.location.reload();

			return;
		}

		if(this.panel)
		{
			this.panel.remove();
			this.panel = null;
			this.list = null;
		}
	};

	private createRow = (file: File): TQueued =>
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
			title : file.name,
			text : file.name
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

		return { file, row, bar, status };
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
			/* The queue has drained: show the listing the uploads are missing from */
			if(this.wrote && this.settled())
			{
				setTimeout(() => window.location.reload(), 600);
			}

			return;
		}

		this.busy = true;

		this.send(queued, () =>
		{
			this.busy = false;
			this.next();
		});
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
			queued.row.classList.remove('active');

			let payload: {
				ok?: boolean;
				error?: string;
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

				this.settle(queued, 'done', getReadableSize(
					config.get('format').sizes, queued.file.size
				).trim());
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
			queued.row.classList.remove('active');
			this.settle(queued, 'failed', 'The connection failed');

			done();
		});

		request.addEventListener('abort', () =>
		{
			queued.row.classList.remove('active');
			this.settle(queued, 'failed', 'Cancelled');

			done();
		});

		/**
		 * Posted back to the directory being viewed, which is the directory
		 * the file is written into: the server resolves the target from the
		 * request path it already validated, so there is no path to send
		 */
		request.open('POST', window.location.pathname, true);
		request.setRequestHeader('Accept', 'application/json');
		request.send(body);
	};
}

export { componentUpload };
