const glob = require('glob');
const path = require('path');
const ESLintWebpackPlugin = require('eslint-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const sass = require('sass');
const webpack = require('webpack');

// Directory Variables
const directories = {
  dist: path.resolve(__dirname, '../styles/all/theme'),
  js_lint_exclusions: [
    path.resolve(__dirname, 'node_modules/')
  ],
  js_compile_exclusions: [
    // path.resolve(__dirname, 'node_modules/jquery')
  ],
  watchIgnore: [
    path.resolve(__dirname, 'node_modules/')
  ]
};

// File sets
const files = {
  tsn_scripts: [
    'jquery',
    // TODO Include any material scripts here
    ...glob.sync(path.resolve(__dirname, 'js/**/*.js'))
  ],

  tsn_theme: [
    path.resolve(__dirname, 'scss/style.scss')
  ]
};

module.exports = function (env = {}) {
  const isDev = env.MODE === 'development';

  // Strip out the empty ones
  Object.keys(files)
    .forEach((key) => (files[key].length === 0) && delete files[key]);

  const config = {
    entry: files,
    mode: 'production',
    module: {
      rules: [
        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            MiniCssExtractPlugin.loader,
            { loader: 'css-loader' },
            {
              loader: 'sass-loader',
              options: {
                implementation: sass,
                // See https://github.com/webpack-contrib/sass-loader/issues/804
                // webpackImporter: false,
                sassOptions: {
                  includePaths: glob.sync('node_modules').map((d) => path.join(__dirname, d))
                }
              }
            }
          ]
        },
        {
          /** @note Consider doing more of the examples here https://webpack.js.org/guides/asset-modules/ */
          test: /\.(jpg|gif|jpeg|png|woff(2)?|eot|ttf|svg)(\?[\w=.]+)?$/,
          dependency: { not: ['url'] },
          use: [
            {
              loader: 'url-loader',
              options: {
                limit: 100000
              }
            }
          ],
          type: 'javascript/auto'
        },
        // {
        //   test: require.resolve('jquery'),
        //   loader: 'expose-loader',
        //   options: {
        //     exposes: ['$', 'jQuery']
        //   }
        // },
        {
          test: /\.js$/,
          loader: 'babel-loader',
          exclude: directories.js_compile_exclusions
          // },
          // // Bring in modernizr - this is not part of package.json anymore, possibly not even needed without IE11 support
          // {
          //   loader: 'webpack-modernizr-loader',
          //   test: /\.modernizrrc\.js$/
        }
      ]
    },
    output: {
      path: directories.dist,
      filename: 'js/[name].js'
    },
    performance: { hints: false },
    plugins: [
      // Lint-check the javascript
      new ESLintWebpackPlugin({
        exclude: directories.js_lint_exclusions,
        emitWarning: true,
        fix: true
      }),
      new webpack.ProvidePlugin({
        $: 'jquery',
        jQuery: 'jquery'
      }),
      new MiniCssExtractPlugin({
        filename: 'css/[name].css'
      })
    ],
    stats: {
      all: false,
      modules: true,
      errors: true,
      warnings: true,
      moduleTrace: true,
      errorDetails: true,
      builtAt: true,
      timings: true
    },
    watch: isDev, // Despite what webpack says, this is still needed.
    watchOptions: {
      ignored: directories.watchIgnore
    }
  };

  return config;
};
