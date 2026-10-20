const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
	entry: {
		// Admin settings page (Vue)
		'govmailbranding-main': path.join(__dirname, 'src', 'main.js'),
		// Loaded on every signed-in page, keep it tiny (no Vue, no deps)
		'govmailbranding-notifications': path.join(__dirname, 'src', 'notifications.js'),
	},
	output: {
		path: path.join(__dirname, 'js'),
		filename: '[name].js',
	},
	module: {
		rules: [
			{ test: /\.vue$/, loader: 'vue-loader' },
			{ test: /\.css$/, use: ['style-loader', 'css-loader'] },
		],
	},
	plugins: [new VueLoaderPlugin()],
	resolve: {
		extensions: ['.js', '.vue'],
	},
}
