require('dotenv').config();

const fs = require('fs');
const gracefulFs = require('graceful-fs');
gracefulFs.gracefulify(fs);
const webpack = require('webpack');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const isProduction = process.env.NODE_ENV === 'production';

module.exports = function(env) {

    var paths = {
        src: './resources/assets/adminCMS/',
        dist: path.resolve(__dirname, 'public/admin'),
        public: '/admin/',
        nodeModules: 'node_modules',
        cmfSrc: 'node_modules/@trikoder/trim/src/',
        cmfNodeModules: 'node_modules/@trikoder/trim/node_modules'
    };

    var webpackConfig = {

        mode: isProduction ? 'production' : 'development',

        entry: {
            main: paths.src + 'js/main.js'
        },

        output: {
            path: paths.dist,
            filename: isProduction ? '[name].[contenthash].js' : '[name].js',
            publicPath: paths.public
        },

        module: {
            rules: [
                {
                    test: /\.vue$/,
                    loader: 'vue-loader'
                },
                {
                    test: /\.(js|vue)$/,
                    loader: 'eslint-loader',
                    enforce: 'pre',
                    include: [
                        path.join(__dirname, 'src')
                    ]
                },

                {
                    test: /\.js$/,
                    use: [
                        {loader: 'babel-loader'},
                        {loader: 'eslint-loader'}
                    ],
                    exclude: /node_modules/,
                    include: [
                        path.join(__dirname, 'src'),
                        path.join(__dirname, 'node_modules/@trikoder/trim/src')
                    ]

                },

                {
                    test: /\.scss$/,
                    exclude: /login\.scss/,
                    use: [
                        'vue-style-loader',
                        'css-loader',
                        'postcss-loader',
                        'sass-loader',
                        {
                            loader: 'sass-resources-loader',
                            options: {
                                resources: [
                                    './node_modules/@trikoder/trim/src/scss/library/_all.scss',
                                    './resources/assets/adminCMS/scss/_overrides.scss'
                                ]
                            }
                        }
                    ]
                },

                {
                    test: /\.(png|jpg|gif|svg)$/,
                    loader: 'file-loader',
                    options: {
                        name: 'images/[name].[ext]'
                    }
                },
                {
                    test: /\.(woff2?|eot|ttf|otf)(\?.*)?$/,
                    loader: 'url-loader',
                    options: {
                        limit: 10000,
                        name: 'fonts/[name].[ext]'
                    }
                },

                {
                    test: /\.css$/,
                    use: [
                        'vue-style-loader',
                        'css-loader',
                        'postcss-loader'
                    ]
                }

            ]
        },

        resolve: {
            modules: [
                paths.src,
                paths.cmfSrc,
                paths.nodeModules,
                paths.cmfNodeModules
            ],
            alias: {
                'vue$': 'vue/dist/vue.esm.js',
                'cmf': '@trikoder/trim/src'
            },
            extensions: ['*', '.js', '.vue', '.json']
        },

        plugins: [

            new VueLoaderPlugin(),

            new webpack.DefinePlugin({
                'process.env': {
                    BASE_URL: JSON.stringify(process.env.BASE_URL),
                    BASE_API_URL: JSON.stringify(process.env.BASE_API_URL),
                    ASSET_PATH: JSON.stringify('/dist/'),
                    NODE_ENV: JSON.stringify(isProduction ? 'production' : 'development')
                }
            }),

            new CopyWebpackPlugin({
                patterns: [
                    {from: `${paths.cmfSrc}font/icons/fonts/**/*`, to: path.resolve(__dirname, 'public') + '/src/font/icons/fonts'},
                    {from: `${paths.cmfSrc}font/webFonts/**/*`, to: path.resolve(__dirname, 'public') + '/src/font/webFonts'}
                ]
            })
        ],

        stats: {
            assets: true,
            excludeAssets: [/.*ckeditor\/.*/]
        }

    };

    if (isProduction) {

        webpackConfig.plugins.push(new webpack.LoaderOptionsPlugin({
            minimize: true,
            debug: false
        }));

        webpackConfig.plugins.push(new webpack.optimize.UglifyJsPlugin({
            comments: false,
            mangle: {
                screw_ie8: true
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
