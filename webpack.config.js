const fs = require('fs');
const gracefulFs = require('graceful-fs');
gracefulFs.gracefulify(fs);
var webpack = require('webpack');
var path = require('path');
var CopyWebpackPlugin = require('copy-webpack-plugin');
var ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = function(env) {

    var paths = {
        src: './resources/assets/adminCMS/',
        dist: path.resolve(__dirname, 'public/admin'),
        public: '/admin/',
        nodeModules: 'node_modules',
        cmfSrc: 'node_modules/trim-cmf/src/',
        cmfNodeModules: 'node_modules/trim-cmf/node_modules'
    };

    var webpackConfig = {

        entry: {
            main: paths.src + 'js/main.js'
        },

        output: {
            path: paths.dist ,
            filename: '[name].js',
            publicPath: paths.public
        },

        module: {
            rules: [

                {
                    test: /\.js$/,
                    use: [
                        {loader: 'babel-loader'},
                        {loader: 'eslint-loader'}
                    ],
                    exclude: /node_modules/
                },

                {
                    test: /\.(jst)$/,
                    use: [
                        {loader: 'nunjucks-loader'}
                    ]
                },

                {
                    test: /\.scss$/,
                    exclude: /login\.scss/,
                    use: [
                        {loader: 'style-loader'},
                        {loader: 'css-loader'},
                        {loader: 'sass-loader', options: {
                            includePaths: [paths.nodeModules, paths.cmfNodeModules]
                        }}
                    ]
                },

                {
                    test: /login\.scss/,
                    use: ExtractTextPlugin.extract({
                        use: [
                            {loader: 'css-loader'},
                            {loader: 'sass-loader', options: {
                                includePaths: [paths.nodeModules, paths.cmfNodeModules]
                            }}
                        ]
                    })
                },

                {
                    test: /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
                    loader: 'url-loader',
                    options: {limit: 10000}
                },

                {
                    test: /\.css$/,
                    use: [
                        {loader: 'style-loader'},
                        {loader: 'css-loader'}
                    ]
                }

            ],
        },

        resolve: {
            modules: [
                paths.src,
                paths.cmfSrc,
                paths.nodeModules,
                paths.cmfNodeModules
            ],
            alias: {
                cmf: path.resolve(__dirname, paths.cmfSrc)
            }
        },

        plugins: [

            new webpack.DefinePlugin({
                'process.env': {
                    'NODE_ENV': JSON.stringify(env.mode),
                    'PUBLIC_PATH': JSON.stringify(paths.public)
                }
            }),

            new CopyWebpackPlugin([{
                from: require('path').dirname(require.resolve('ckeditor')),
                to: paths.dist + '/ckeditor',
                ignore: ['*.php']
            }]),

            new CopyWebpackPlugin([
                {
                    from: `${paths.cmfSrc}font/icons/fonts`,
                    to: path.resolve(__dirname, 'public') + '/src/font/icons/fonts',
                },
                {
                    from: `${paths.cmfSrc}font/webFonts`,
                    to: path.resolve(__dirname, 'public') + '/src/font/webFonts',
                },
            ]),


            new ExtractTextPlugin('[name].css'),

        ],

        stats : {
            assets : true,
            excludeAssets : [/.*ckeditor\/.*/]
        }

    };

    if (env.mode === 'production') {

        webpackConfig.plugins.push(new webpack.LoaderOptionsPlugin({
            minimize: true,
            debug: false
        }));

        webpackConfig.plugins.push(new webpack.optimize.UglifyJsPlugin({
            comments: false,
            mangle: {
                screw_ie8: true,
            },
            compress: {
                screw_ie8: true,
                warnings: false
            }
        }));

        webpackConfig.plugins.push(new webpack.optimize.ModuleConcatenationPlugin());

    }

    return webpackConfig;

};
