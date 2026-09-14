<h1 align="center">Themes</h1>

<p align="center">Overview and theme usage.</p>


## Overview
IVFi-PHP supports custom themes. By default, you can place any themes in `/indexer/themes/`, and they'll be automatically applied. If you want to customize the path or the default theme, then you can use the below instructions.

You can find a list of official themes here: [IVFi-themes](https://github.com/sixem/ivfi-themes), or you can create your own!

The page is styled with Tailwind CSS and the shadcn design tokens, so most of the look comes from a small set of CSS custom properties. Themes written for the older stylesheet target the same class names but expect its dark colors and spacing, so they may not look right any more.

## Writing a theme

The simplest theme redefines the tokens. It loads after the base stylesheet, so this is enough to change the accent color and round the corners less:

```css
:root {
  --primary: oklch(0.62 0.19 259.8);
  --primary-foreground: oklch(0.98 0 0);
  --radius: 0.375rem;
}

@media (prefers-color-scheme: dark) {
  :root {
    --primary: oklch(0.7 0.16 254.6);
  }
}
```

The page follows the visitor's light or dark system setting. The available tokens are listed at the top of `src/css/theme.css`: `--background`, `--foreground`, `--card`, `--popover`, `--primary`, `--secondary`, `--muted`, `--accent`, `--destructive`, `--border`, `--input`, `--ring` and `--radius`, most with a matching `-foreground`. Two layout values, `--topbar-height` and `--row-height`, can be changed too.

File icons are colored by kind with `--file-image`, `--file-video`, `--file-audio`, `--file-pdf`, `--file-document`, `--file-spreadsheet`, `--file-presentation`, `--file-archive`, `--file-package`, `--file-code`, `--file-data`, `--file-font` and `--file-key`. Each file row carries its kind as `data-kind`, and its icon takes the color of `--file-kind` set on that row, so a theme can also recolor a single kind there, for example `tr[data-kind="pdf"] { --file-kind: oklch(0.6 0.2 30); }`. Setting `color` on the row does not reach the icon. Files of no known kind have no `data-kind` and keep the muted icon.

## Usage
* 1) Download or create the themes that you wish to use.
* 2) Place them in a publicly available directory.
	* Example: `/indexer/themes/`
* 3) Edit the configuration:
	* Set the `path` to the relative directory of the themes.
	* If you want a theme to be the default, then set `default` to the theme's name.
```php
<?php
return [
		'style' => [
			'themes' => [
				'path' => '/indexer/themes/',
				'default' => false
			]
		]
];
?>
```
* 4) You should now be able to enable different themes in the settings menu (the menu button in the top right corner, then Settings).