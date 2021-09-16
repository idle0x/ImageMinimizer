<?php


namespace Idleo\ImageOptimizer;


use Exception;

/**
 * Via magic method
 * @property int height Output image height
 * @property int width Output image width
 * @property int quality Image quality 0-100
 * @property string fit Look API documentation https://sharp.pixelplumbing.com/api-resize
 * @property string extension Output file extension
 * @property string filename Output file name
 * @property string source Absolute source path
 * @property string output Absolute output directory
 * @property string excludePlugins Plugins excluded for svg optimization
 */
class ImgParams
{
    public const REQUIRED_PARAMS = [
        'source', 'output', 'filename', 'extension',
    ];

    public const ADDITIONAL_PARAMS_RASTER = [
        'width', 'height', 'fit', 'quality',
    ];

    public const ADDITIONAL_PARAMS_SVG = [
        'excludePlugins',
    ];

    public const AVAILABLE_EXTENSIONS_RASTER = [
        'png', 'jpg', 'jpeg', 'gif', 'webp'
    ];

    public const AVAILABLE_EXTENSIONS_SVG = [
        'svg'
    ];

    private const AVAILABLE_SVGO_PLUGINS = [
        'removeDoctype',
        'removeXMLProcInst',
        'removeComments',
        'removeMetadata',
        'removeXMLNS',
        'removeEditorsNSData',
        'cleanupAttrs',
        'mergeStyles',
        'inlineStyles',
        'minifyStyles',
        'convertStyleToAttrs',
        'cleanupIDs',
        'prefixIds',
        'removeRasterImages',
        'removeUselessDefs',
        'cleanupNumericValues',
        'cleanupListOfValues',
        'convertColors',
        'removeUnknownsAndDefaults',
        'removeNonInheritableGroupAttrs',
        'removeUselessStrokeAndFill',
        'removeViewBox',
        'cleanupEnableBackground',
        'removeHiddenElems',
        'removeEmptyText',
        'convertShapeToPath',
        'convertEllipseToCircle',
        'moveElemsAttrsToGroup',
        'moveGroupAttrsToElems',
        'collapseGroups',
        'convertPathData',
        'convertTransform',
        'removeEmptyAttrs',
        'removeEmptyContainers',
        'mergePaths',
        'removeUnusedNS',
        'sortAttrs',
        'sortDefsChildren',
        'removeTitle',
        'removeDesc',
        'removeDimensions',
        'removeAttrs',
        'removeAttributesBySelector',
        'removeElementsByAttr',
        'addClassesToSVGElement',
        'removeStyleElement',
        'removeScriptElement',
        'addAttributesToSVGElement',
        'removeOffCanvasPaths',
        'reusePaths',
    ];

    private array $params = [];

    /**
     * @throws Exception
     */
    public function __construct(array $imageParams = [])
    {
        $this->setParams($imageParams);
    }

    /**
     * @param string $name
     * @param $value
     * @return bool
     * @throws Exception
     */
    public function __set(string $name, $value)
    {
        if (!in_array($name, self::REQUIRED_PARAMS)
            && !in_array($name, self::ADDITIONAL_PARAMS_RASTER)
            && !in_array($name, self::ADDITIONAL_PARAMS_SVG)
        ) {
            return false;
        }

        if ('source' == $name && !file_exists($value)) {
            throw new Exception("Source file is not existed or available ($value)");
        }

        if ('output' == $name && !is_dir($value)) {
            throw new Exception("Output directory is not existed or available ($value)");
        }

        if ('extension' == $name 
            && !in_array($value, self::AVAILABLE_EXTENSIONS_RASTER)
            && !in_array($value, self::AVAILABLE_EXTENSIONS_SVG)
        ) {
            throw new Exception("Extension is not available ($value)");
        }

        if ('quantity' == $name) {
            $value = (int)$value;
            $value = $value > 100 ? 100 : $value;
        }

        if ('width' == $name) {
            $value = (int)$value;
        }

        if ('width' == $name) {
            $value = (int)$value;
        }

        if ('excludePlugins' == $name && !in_array($value, self::AVAILABLE_SVGO_PLUGINS)) {
            throw new Exception("Plugin for exclude is wrong");
        }

        $this->params[$name] = $value;

        return true;
    }

    /**
     * @param array $imageParams
     * @throws Exception
     */
    public function setParams(array $imageParams)
    {
        if (!empty($imageParams)) {
            foreach ($imageParams as $name => $value) {
                $this->__set($name, $value);
            }
        }
    }

    //region Function for fast set parameters
    /**
     * Only for raster image
     * @param int $value
     * @return $this
     */
    public function setWidth(int $value): ImgParams
    {
        $this->width = $value;
        return $this;
    }

    /**
     * Only for raster image
     * @param int $value
     * @return $this
     */
    public function setHeight(int $value): ImgParams
    {
        $this->height = $value;
        return $this;
    }

    /**
     * Only for raster image
     * @param int $value
     * @return $this
     */
    public function setQuality(int $value): ImgParams
    {
        $this->quality = $value;
        return $this;
    }

    /**
     * Only for raster image
     * @param string $value
     * @return $this
     */
    public function setFit(string $value): ImgParams
    {
        $this->fit = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setExtension(string $value): ImgParams
    {
        $this->extension = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setFilename(string $value): ImgParams
    {
        $this->filename = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setSource(string $value): ImgParams
    {
        $this->source = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setOutput(string $value): ImgParams
    {
        $this->output = $value;
        return $this;
    }

    /**
     * Only for svg image
     * @param string $value String of plugins coma separated
     * @return $this
     */
    public function setExcludePlugins(string $value): ImgParams
    {
        $this->excludePlugins = $value;
        return $this;
    }
    //endregion

    /**
     * Get image params
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return bool
     */
    public function isRequiredParamsFilled(): bool
    {
        $count = 0;
        $currentParams = array_keys($this->params);
        foreach ($this::REQUIRED_PARAMS as $param) {
            if (in_array($param, $currentParams)
                && !empty($this->params[$param])
            ) {
                $count++;
            }
        }
        return count($this::REQUIRED_PARAMS) === $count;
    }

    /**
     * @param string $name
     * @return false|mixed
     */
    public function __get(string $name)
    {
        if (!empty($this->params[$name])) {
            return $this->params[$name];
        }
    }
}
