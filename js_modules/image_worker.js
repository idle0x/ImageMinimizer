
const sharp = require('sharp')
const fs = require('fs');
const svgo = require('svgo');
const {defaultPlugins} = require('svgo/lib/svgo/config');

class ImageWorker {
  /** Parameters required for all image format */
  #requiredParams = [
    'source',
    'output',
    'filename',
    'extension'
  ];
  /** Only raster parameters */
  #additionalParamsRaster = [
    'width',
    'height',
    'fit',
    'quality'
  ]
  /** Only svg parameters */
  #additionalParamsSvg = [
    'excludePlugins',
  ]
  
  #compatibleExtensionsRaster = [
    'jpg',
    'jpeg',
    'webp',
    'png',
  ]

  #compatibleExtensionsVector = [
    'svg',
  ]

  constructor(params) {
    this.options = {}
    this.imgHandle = null;
    this.#checkParams(params)

    console.log(this.options);

    // Check source and output
    if (!fs.existsSync(this.options.source)) {
      throw new Error(`Source file ${this.options.source} not exist.`)
    }
    if (!fs.existsSync(this.options.output)) {
      throw new Error(`Directory ${this.options.output} not exist.`)
    }
  }

  #checkParams(params) {
    for (let name in params) {
      let value = params[name]
      if (this.#requiredParams.indexOf(name) !== -1) {
        this.options[name] = value
        if ('extension' === name) {
          switch (value) {
            case 'svg':
              // Для svg будет вызван метод обработки через svgo
              this.imgHandle = 'formatSvg';
              break;
            default:
              this.imgHandle = 'formatRaster';
              if(this.#compatibleExtensionsRaster.indexOf(value) !== -1) {
                // Параметр процесса обработки для jpg должен быть именно jpeg
                if ('jpg' === value) {
                  this.options.extProc = 'jpeg'
                } else {
                  this.options.extProc = value
                }
              } else {
                throw new Error('Format of image is not compatible.');
              }
          }
        }
      }
      if (this.#additionalParamsRaster.indexOf(name) !== -1) {
        if ('quality' !== name) {
          if (!this.options.resize) {
            this.options.resize = {}
          }
          this.options.resize[name] = value
        } else {
          if (!this.options.quality) {
            this.options.quality = {}
          }
          this.options.quality[name] = Number.parseInt(value)
        }
      }
      if (this.#additionalParamsSvg.indexOf(name) !== -1) {
        this.options.plugins = defaultPlugins.filter(item => {
          return value.split(',').indexOf(item) === -1;
        });
      }
    }
  }

  format() {
    this[this.imgHandle]();
  }

  formatRaster() {
    console.log("format raster");
    // let files = glob.sync(this.options.source);
    // files.forEach(file => {
    //   let sharpInstance = sharp(file);
    //   if (this.options.resize) {
    //     sharpInstance.resize(this.options.resize)
    //   }
    //   if (this.options.quality) {
    //     sharpInstance[this.options.extProc](this.options.quality)
    //   }
    //   sharpInstance.toFile(`${this.options.output}/${this.options.filename}.${this.options.extension}`)
    //     .catch(err => {
    //       throw new Error(err)
    //     })
    // })
  }

  formatSvg() {
    try {
      let content = fs.readFileSync(this.options.source, 'utf8')
      if (content) {
        const result = svgo.optimize(content);
        if (result && this.options.output) {
          fs.writeFileSync(`${this.options.output}/${this.options.filename}.${this.options.extension}`, result.data);
        }
      }
    } catch (e) {
      console.log("Error: ", e);
    }    
  }
}

module.exports = ImageWorker
