const config = require('talk-webpack-config');
const { merge } = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'talkcenter',
  },
});
