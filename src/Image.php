<?php


namespace Idleo\ImageOptimizer;


use Exception;
use Idleo\ImageOptimizer\RasterImg;
use Idleo\ImageOptimizer\SvgImg;

/**
 * Class Image
 * @package Idleo\ImageOptimizer
 */

class Image
{
    private static $instance;
    /**
     * @param string $source
     * @return RasterImg
     */
    public static function getRaster(string $source): RasterImg
    {
        static::$instance = new RasterImg($source);
        return static::$instance;
    }
    /**
     * @param string $source
     * @return SvgImg
     */
    public static function getSvg(string $source): SvgImg
    {
        static::$instance = new SvgImg($source);
        return static::$instance;
    }

    //region Helpers for short code
    /**
     * @param string $source
     * @return RasterImg
     */
    public static function optimizeRaster(string $source): RasterImg
    {
        static::$instance = new RasterImg($source, [], false);
        return static::$instance;
    }
    /**
     * @param string $source
     * @return SvgImg
     */
    public static function optimizeSvg(string $source): SvgImg
    {
        static::$instance = new SvgImg($source, [], false);
        return static::$instance;
    }
    /**
     * @param string $source
     * @return RasterImg
     */
    public static function pasteRaster(string $source): RasterImg
    {
        static::$instance = new RasterImg($source);
        return static::$instance;
    }
    /**
     * @param string $source
     * @return SvgImg
     */
    public static function pasteSvg(string $source): SvgImg
    {
        static::$instance = new SvgImg($source);
        return static::$instance;
    }
    //endregion

    /**
     * Возвращает экземпляр параметров изображения для кастомизации
     * @return mixed
     * @throws Exception
     */
    public static function cloneParams()
    {
        if (!empty(static::$instance)) {
            return static::$instance->cloneParams();
        } else {
            throw new Exception('Instance empty');
        }
    }
    /**
     * Возвращает строку " minimized" в случае успешной оптимизации изображения на последнем инстансе.
     * Необходимо для применения в sectionHero с mixin bg-minimized.
     * @return string
     */
    public static function classMinimized(): string
    {
        return static::$instance->isOptimizationCorrect() ? ' minimized' : '';
    }
}
