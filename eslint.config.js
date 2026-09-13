import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';

export default tseslint.config(
	{
		/* Vendored code is linted by its own authors, and the PHP is not JavaScript */
		ignores: ['src/core/vendors/**', 'src/php/**', 'build/**']
	},
	js.configs.recommended,
	...tseslint.configs.recommended,
	{
		files: ['src/core/**/*.ts'],
		plugins: {
			'@stylistic': stylistic
		},
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module',
			globals: {
				...globals.browser,
				...globals.node,
				Modernizr: 'readonly'
			}
		},
		rules: {
			'no-var': 'warn',
			'no-dupe-class-members': 'error',
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/no-namespace': 'off',
			'@typescript-eslint/no-this-alias': 'off',
			'@typescript-eslint/no-empty-function': 'off',
			/* The formatting rules left ESLint core, so they come from @stylistic */
			'@stylistic/indent': ['error', 'tab', { SwitchCase: 1 }],
			'@stylistic/quotes': ['error', 'single'],
			'@stylistic/semi': ['error', 'always']
		}
	}
);
