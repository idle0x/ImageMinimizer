console.log("test");
const yargs = require('yargs/yargs')
const { hideBin } = require('yargs/helpers')
const argv = yargs(hideBin(process.argv)).argv
const ImageWorker = require('./js_modules/image_worker');

let params = {}
for (let name in argv) {
  if (argv.hasOwnProperty(name)) {
    params[name] = argv[name]
  }
}

console.log(params);

try {
  (new ImageWorker(params)).format()
} catch (e) {
  console.log(e)
}
