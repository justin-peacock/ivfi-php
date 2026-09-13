import { DOM } from './dom';

/**
 * Confirmations and prompts drawn in the page, in place of `window.confirm()`,
 * `window.prompt()` and `window.alert()`.
 *
 * Some browsers suppress the native dialogs outright, embedded and in-app
 * browsers especially, and then `confirm()` quietly returns false: a button
 * that asks first does nothing at all, with no sign why. A `<dialog>` opened
 * with `showModal()` cannot be suppressed, and it brings focus trapping, Escape
 * to cancel, and an inert page behind it without any of that written here.
 */

type TDialogOptions = {
	title: string;
	description?: string;
	/** Present for a prompt: the accessible label of its text field */
	input?: {
		label: string;
		placeholder?: string;
	};
	confirmLabel: string;
	/** Absent for an alert, which only acknowledges */
	cancelLabel?: string;
	destructive?: boolean;
};

let sequence = 0;

/**
 * Opens a dialog and settles once it closes.
 *
 * Resolves with the field's value (an empty string when there is no field)
 * when confirmed, and with null when cancelled, dismissed with Escape, or
 * closed any other way
 */
const open = (options: TDialogOptions): Promise<string | null> =>
{
	return new Promise((resolve) =>
	{
		const id = `ivfiDialog${++sequence}`;

		const dialog = DOM.new('dialog', {
			class : 'ivfiDialog',
			'aria-labelledby' : `${id}Title`
		}) as HTMLDialogElement;

		/* `method="dialog"` closes with the submitting button's value, and never navigates */
		const form = DOM.new('form', {
			method : 'dialog'
		});

		form.append(DOM.new('div', {
			id : `${id}Title`,
			class : 'ivfiDialogTitle',
			text : options.title
		}));

		if(options.description)
		{
			dialog.setAttribute('aria-describedby', `${id}Description`);

			form.append(DOM.new('p', {
				id : `${id}Description`,
				class : 'ivfiDialogDescription',
				text : options.description
			}));
		}

		let input: HTMLInputElement = null;

		if(options.input)
		{
			input = DOM.new('input', {
				type : 'text',
				autocomplete : 'off',
				spellcheck : 'false',
				'aria-label' : options.input.label,
				placeholder : options.input.placeholder || ''
			}) as HTMLInputElement;

			form.append(input);
		}

		const footer = DOM.new('div', {
			class : 'ivfiDialogFooter'
		});

		let cancel: HTMLButtonElement = null;

		if(options.cancelLabel)
		{
			/**
			 * A plain button, not a submit one. Enter in the text field submits
			 * with the form's first submit button, which has to be the one that
			 * confirms
			 */
			cancel = DOM.new('button', {
				type : 'button',
				'data-variant' : 'outline',
				text : options.cancelLabel
			}) as HTMLButtonElement;

			cancel.addEventListener('click', () => dialog.close('cancel'));

			footer.append(cancel);
		}

		const confirm = DOM.new('button', {
			type : 'submit',
			value : 'confirm',
			'data-variant' : options.destructive ? 'destructive' : 'default',
			text : options.confirmLabel
		}) as HTMLButtonElement;

		footer.append(confirm);
		form.append(footer);
		dialog.append(form);

		/**
		 * Browsers cancel a modal dialog on Escape themselves. Handled here as
		 * well for key events that do not trigger that, which some embedded
		 * browsers and automation send; closing an already closed dialog is a
		 * no-op, so the two never both act
		 */
		dialog.addEventListener('keydown', (event: KeyboardEvent) =>
		{
			if(event.key === 'Escape')
			{
				event.preventDefault();
				dialog.close('cancel');
			}
		});

		dialog.addEventListener('close', () =>
		{
			const confirmed = dialog.returnValue === 'confirm';

			dialog.remove();

			resolve(confirmed ? (input ? input.value : '') : null);
		});

		document.body.append(dialog);
		dialog.showModal();

		/* A destructive question starts on the safe answer */
		if(input)
		{
			input.focus();
		} else if(options.destructive && cancel)
		{
			cancel.focus();
		} else {
			confirm.focus();
		}
	});
};

export const dialogs = {
	/**
	 * Asks a yes or no question, resolving with whether it was confirmed
	 */
	confirm: (title: string, options: {
		description?: string;
		confirmLabel?: string;
		destructive?: boolean;
	} = {}): Promise<boolean> =>
	{
		return open({
			title,
			description: options.description,
			confirmLabel: options.confirmLabel || 'Continue',
			cancelLabel: 'Cancel',
			destructive: options.destructive
		}).then((value) => value !== null);
	},

	/**
	 * Asks for a line of text, resolving with it, or with null when cancelled
	 */
	prompt: (title: string, options: {
		label: string;
		description?: string;
		placeholder?: string;
		confirmLabel?: string;
	}): Promise<string | null> =>
	{
		return open({
			title,
			description: options.description,
			input: {
				label: options.label,
				placeholder: options.placeholder
			},
			confirmLabel: options.confirmLabel || 'OK',
			cancelLabel: 'Cancel'
		});
	},

	/**
	 * Tells the client something, resolving once it is acknowledged
	 */
	alert: (title: string, description?: string): Promise<void> =>
	{
		return open({
			title,
			description,
			confirmLabel: 'OK'
		}).then((): void => undefined);
	}
};
