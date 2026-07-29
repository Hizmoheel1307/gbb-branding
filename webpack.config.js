const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
	entry: path.join(__dirname, 'src', 'main.js'),
	output: {
		path: path.join(__dirname, 'js'),
		filename: 'govmailbranding-main.js',
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
