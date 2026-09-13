/**
 * The `this` context threaded through this module (and through
 * `hover-preview.ts` and `events.ts`) is the preview instance created in
 * `hover-preview.ts`'s `setup()`. Its properties are set dynamically,
 * outside any one function, and are read and written across all three
 * files, so there is no single place that "owns" the shape.
 *
 * Giving it a precise type is real work, and belongs with the broader
 * `noImplicitAny` pass staged in docs/building.md, alongside the rest of
 * this module's untyped parameters. Here `this` only needs an explicit
 * annotation to satisfy `noImplicitThis`, so it is given the type it
 * already had implicitly.
 */
export type HoverPreviewInstance = any;

/**
 * What `loadImage()` and `loadVideo()` hand to their shared caller in
 * events.ts, which only ever appends the element without caring which kind
 * it is
 */
export type HoverPreviewMedia = HTMLImageElement | HTMLVideoElement;

/** The callback both `loadImage()` and `loadVideo()` take */
export type HoverPreviewLoadCallback = (
	result: false | HoverPreviewMedia,
	dimensions?: [number, number]
) => void;

function getLeft(left: boolean, eWidth: number, offsetX: number): number
{
	if(left)
	{
		if((window.innerWidth - (offsetX) - 20) > (eWidth))
		{
			return offsetX + 20;
		} else if(window.innerWidth > eWidth)
		{
			return (window.innerWidth - eWidth);
		}
	} else {
		if(eWidth < (offsetX - 20))
		{
			return (offsetX - eWidth - 20);
		} else {
			return 0;
		}
	}

	return 0;
}

function getTop(offset: { x: number; y: number }, dimensions: { x: number; y: number }): number
{
	var wHeight = window.innerHeight;

	if(dimensions.y >= wHeight)
	{
		return 0;
	}

	var percentage = (offset.y / wHeight * 100);

	percentage = percentage > 100 ? 100 : percentage;

	return (wHeight / 100 * percentage - (dimensions.y) / 100 * percentage);
}

function move(left: boolean, element: HTMLElement, data: { offset: { x: number; y: number }; dimensions: { x: number; y: number } })
{
	var offset = data.offset, dimensions = data.dimensions;

	element.style['left'] = getLeft(left, element.clientWidth, offset.x) + 'px';
	element.style['top'] = getTop(offset, dimensions) + 'px';

	return false;
}

export function getMove()
{
	if(window.requestAnimationFrame)
	{
		return function(left: boolean, element: HTMLElement, data: { offset: { x: number; y: number }; dimensions: { x: number; y: number } })
		{
			window.requestAnimationFrame(function()
			{
				move(left, element, data);
			});
		};
	}

	return function(left: boolean, element: HTMLElement, data: { offset: { x: number; y: number }; dimensions: { x: number; y: number } })
	{
		move(left, element, data);
	};
}

export function getType(this: HoverPreviewInstance)
{
	if(this.data.force)
	{
		this.data.extension = this.data.force.extension;
		return this.data.force.type;
	}

	this.data.extension = this.data.src.split('.').pop().toLowerCase();

	if(['jpg', 'jpeg', 'gif', 'png', 'ico', 'svg', 'bmp', 'webp'].includes(this.data.extension))
	{
		return 0;
	} else if(['webm', 'mp4', 'ogg', 'ogv', 'mov'].includes(this.data.extension))
	{
		return 1;
	}

	return null;
}

export function createContainer()
{
	var container = document.createElement('div');

	container.className = 'preview-container';

	var styles: { [key: string]: string } = {
		'pointer-events' : 'none',
		'position' : 'fixed',
		'visibility' : 'hidden',
		'z-index' : '9999',
		'top' : '-9999px',
		'left' : '-9999px',
		'max-width' : '100vw',
		'max-height' : '100vh'
	};

	Object.keys(styles).forEach((key) =>
	{
		/* See the identical cast in DOM.style.set() for why this is safe */
		(container.style as unknown as Record<string, string>)[key] = styles[key];
	});

	return container;
}

function encodeUrl(this: HoverPreviewInstance, input: string): string
{
	return this.options.encodeAll ? input.replace('#', '%23').replace('?', '%3F') : encodeURI(input);
}

/**
 * `webkitAudioDecodedByteCount`, `mozHasAudio` and `audioTracks` are
 * non-standard, browser-specific ways to detect whether a video has an
 * audio track; none of the three is in the standard `HTMLVideoElement`
 */
interface IAudioDetectableVideo extends HTMLVideoElement {
	webkitAudioDecodedByteCount?: number;
	mozHasAudio?: boolean;
	audioTracks?: { length: number };
}

function isAudible(video: IAudioDetectableVideo): boolean
{
    if(typeof video.webkitAudioDecodedByteCount !== 'undefined')
    {
        if(video.webkitAudioDecodedByteCount > 0)
        {
        	return true;
        } else {
        	return false;
        }
    } else if(typeof video.mozHasAudio !== 'undefined')
    {
        if(video.mozHasAudio)
        {
            return true;
        } else {
            return false;
        }
    } else if(typeof video.audioTracks !== 'undefined')
    {
        if(video.audioTracks && video.audioTracks.length)
        {
            return true;
        } else {
            return false;
        }
    } else {
    	return false;
    }

    return false;
}

export function loadImage(this: HoverPreviewInstance, src: string, callback: HoverPreviewLoadCallback)
{
	var _this = this;

	var img = document.createElement('img');

	this.currentElement = img;

	/* See the identical cast in DOM.style.set() for why this is safe */
	(img.style as unknown as Record<string, string>)['max-width'] = 'inherit';
	(img.style as unknown as Record<string, string>)['max-height'] = 'inherit';

	img.src = encodeUrl.call(_this, src);

	_this.timers.load = setInterval(function()
	{
		if(!_this.active)
		{
			callback(false);

			return;
		}

		var w = img.naturalWidth, h = img.naturalHeight;
				
		if(w && h)
		{
			if(_this.data.on.hasOwnProperty('onLoaded'))
			{
				try
				{
					_this.data.on.onLoaded({
						loaded : true,
						type : 'IMAGE',
						audible : false,
						element : img,
						src : src
					});
				} catch(error)
				{
					console.error(error);
				}
			}

			clearInterval(_this.timers.load);

			callback(img, [w, h]);
		}
	}, 30);
}

export function loadVideo(this: HoverPreviewInstance, src: string, callback: HoverPreviewLoadCallback)
{
	var _this = this;

	var video = document.createElement('video');

	var source = video.appendChild(document.createElement('source'));

	this.currentElement = video;

	(['muted', 'loop', 'autoplay'] as Array<keyof HTMLVideoElement>).forEach((key) =>
	{
		(video[key] as boolean) = true;
	});

	source.type = 'video/' + (this.data.extension === 'mov' ? 'mp4' : (this.data.extension === 'ogv' ? 'ogg' : this.data.extension));

	source.src = encodeUrl.call(this, src);

	/* See the identical cast in DOM.style.set() for why this is safe */
	(video.style as unknown as Record<string, string>)['max-width'] = 'inherit';
	(video.style as unknown as Record<string, string>)['max-height'] = 'inherit';

	video.onloadeddata = function()
	{
		if(!_this.active)
		{
			callback(false);

			return;
		}

		if(_this.data.on.hasOwnProperty('onLoaded'))
		{
			try
			{
				_this.data.on.onLoaded({
					loaded : true,
					type : 'VIDEO',
					audible : isAudible(video),
					element : video,
					src : src
				});
			} catch(error)
			{
				console.error(error);
			}
		}
	};

	video.onloadedmetadata = function(event)
	{
		let eventTarget: HTMLVideoElement = event.target as HTMLVideoElement;

		if(!_this.active)
		{
			callback(false);

			return;
		}

		callback(video, [eventTarget.videoWidth, eventTarget.videoHeight]);
	}; 
}