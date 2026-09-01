/**
 * ESLint flat config.
 *
 * ESLint 9+ (bundled from @wordpress/scripts 32 onward) uses flat config by
 * default and treats .eslintrc.* as a deprecated fallback, so this replaces
 * the old .eslintrc.js.
 *
 * Mirrors the default config @wordpress/scripts ships, plus browser globals:
 * the WordPress recommended config declares none, so `navigator` and friends
 * raise false no-undef errors in source that only ever runs in a browser.
 */

const globals = require('globals');
const wpPlugin = require('@wordpress/eslint-plugin');

module.exports = [
	{
		ignores: [
			'**/build/**',
			'**/node_modules/**',
			'**/vendor/**',
			// Verbatim copies of the wordpress-claude-code plugin's hook
			// scripts. Reformatting them would create drift from upstream.
			'.claude/**',
		],
	},

	...wpPlugin.configs.recommended,

	...wpPlugin.configs['test-unit'].map((config) => ({
		...config,
		files: ['**/@(test|__tests__)/**/*.js', '**/?(*.)test.js'],
	})),

	{
		languageOptions: {
			globals: {
				...globals.browser,
			},
			parserOptions: {
				requireConfigFile: false,
				babelOptions: {
					presets: [
						require.resolve('@wordpress/babel-preset-default'),
					],
				},
			},
		},
	},
];
