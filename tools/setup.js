'use strict';

const { systemCmd } = require('./lib/system.js');

(async () => {
  try {
    await systemCmd('npm ci');
    await systemCmd('composer install --no-dev');
  } catch (err) {
    console.log(err);
  }
})();
