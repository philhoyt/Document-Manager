const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'edit-screen': path.resolve( __dirname, 'src/edit-screen/index.js' ),
		'list-table': path.resolve( __dirname, 'src/list-table/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
