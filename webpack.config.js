const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    simple: './Resources/Private/JavaScript/simple.js',
    simple_de: './Resources/Private/JavaScript/simple_de.js'
  },
  output: {
    path: path.resolve(__dirname, './Resources/Public/Uppload'),
  },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              publicPath: path.resolve(__dirname, './Resources/Public/Uppload'),
            },
          },
          'css-loader'
        ],
      }
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  ],
};
