<?php


namespace Idleo\ImageOptimizer;

use Bitrix\Main\Context;
use Bitrix\Main\Page\AssetLocation;
use Exception;
use Taxcom\Tool\ImageWorker\RasterWorker;
use Taxcom\Tool\Profile;
use Yiisoft\Html\Html;
use Tax\Bitrix\Asset;
use Tab\Server\Env;
use Taxcom\Tool\ImageWorker\Contracts\ImageParamsInterface;

class ImgRaster extends BaseImg
{
    /** @const BREAKPOINTS  Набор размеров ширины под размеры бреакпоинтов */
    private const BREAKPOINTS = [
        'es' => '320',
        'sm' => '576',
        'md' => '768',
        'lg' => '962',
        'xl' => '1200',
        'xxl' => '' // Width like source
    ];
    // Optional configurations
    /** * @var bool Set for output image html attribute lazyload */
    private bool $lazyload = true;
    /** * @var bool Set for output additional page property <link> for preloading image */
    private bool $preload = false;
    /** * @var bool Resize images by breakpoints */
    private bool $byBreakpoints = true;
    /** * @var bool For IE compatible make optimized picture with origin size */
    private bool $makeDefaultImage = true;

    /** @var array Customize list of breakpoints */
    private array $customBreakpoints = [];
    /** @var array Classes for add in image */
    private array $imgClasses = [];

    public function __construct($source, $options = [], $pasteImg = true)
    {
        $this->worker = new ImgWorker(
            new ImgParams(),
            substr(realpath($_SERVER['DOCUMENT_ROOT']), 0, -12),
            false
        );
        $this->defaultExtension = 'webp';
        $this->prepare($source, $options);
        $this->returnContent($pasteImg);
    }

    /**
     * @throws Exception
     */
    private function parseOptionsArray($options)
    {
        foreach ($options as $name=>$option) {
            switch($name) {
                case 'returnHtml':
                    $this->returnHtml = (bool)$option;
                    break;
                case 'byBreakpoints':
                    $this->byBreakpoints = (bool)$option;
                    break;
                case 'lazyload':
                    $this->lazyload = (bool)$option;
                    break;
                case 'preload':
                    $this->preload = (bool)$option;
                    break;
                case 'makeDefaultImage':
                    $this->makeDefaultImage = (bool)$option;
                    break;
                case 'workerOptions':
                    if (is_array($option)) {
                        $this->setWorkerOptionsByArray($option);
                    }
                    break;
            }
        }
    }

    //region Methods for settings

    /**
     * Воркер должен создать минификации по бреакпоинтам
     * @param bool $resizeImageByBreakpoints
     * @return $this
     */
    public function byBreakpoints(bool $resizeImageByBreakpoints): RasterImg
    {
        $this->byBreakpoints = $resizeImageByBreakpoints;
        return $this;
    }

    /**
     * Воркер должен добавить атрибут loading="lazy"
     * @param bool $setLazyloadAttr
     * @return $this
     */
    public function setLazyload(bool $setLazyloadAttr = true): RasterImg
    {
        // Disable preload
        $this->preload = false;

        $this->lazyload = $setLazyloadAttr;
        return $this;
    }


    /**
     * Воркер должен добавить прелоад для изображения
     * @param bool $setPreloadAttr
     * @return $this
     */
    public function setPreload(bool $setPreloadAttr = true): RasterImg
    {
        // Disable lazyload
        $this->lazyload = false;

        $this->preload = $setPreloadAttr;
        return $this;
    }

    public function setClasses(array $classes): RasterImg
    {
        $this->imgClasses = $classes;
        return $this;
    }

    /**
     * Воркер должен создать дефолтное изображение - c расширением исходника, но минифицированное
     * @param bool $makeDefault
     * @return $this
     */
    public function makeDefaultImage(bool $makeDefault): RasterImg
    {
        $this->makeDefaultImage = $makeDefault;
        return $this;
    }

    /**
     * Передать массив объектов типа RasterParams для создания минификаций под свои требования
     * @param array $options
     * @return $this
     */
    public function setWorkerOptions(array $options): ImgRaster
    {
        // Отключаем дефолтное создание по брейкпоинтам
        $this->byBreakpoints = false;
        foreach ($options as $option) {
            if ($option instanceof ImgParams) {
                $this->workerParams[] = $option;
            }
        }
        return $this;
    }

    /**
     * Передать массив массивов (параметров изображений) если не подходит setWorkerOptions
     * @param array $options
     * @return $this
     * @throws Exception
     */
    public function setWorkerOptionsByArray(array $options): RasterImg
    {
        // Отключаем дефолтное создание по брейкпоинтам
        $this->byBreakpoints = false;
        foreach ($options as $arOption) {
            $option = $this->cloneParams();
            $option->setParams($arOption);
            $this->workerParams[] = $option;
        }
        return $this;
    }

    /**
     * Задать кастомный набор бреакпоинтов (если нужны минификации не для всех разрешений)
     * @param array $breakpoints
     * @return $this
     */
    public function customizeBreakpoints(array $breakpoints): RasterImg
    {
        foreach ($breakpoints as $breakpoint) {
            if (in_array($breakpoint, array_keys($this::BREAKPOINTS))) {
                $this->customBreakpoints[] = $breakpoint;
            }
        }
        return $this;
    }
    //endregion

    /**
     * Execute worker for each instance of image params
     *
     * @return false|string
     * @throws Exception
     */
    public function run()
    {
        try {
            // Проверяем настройку брейкпоинтов
            if ($this->byBreakpoints) {
                $this->makeOptionsByBreakpoints();
            } else {
                // Добавляем параметры изображений если не были добавлены ранее
                if (empty($this->workerParams)) {
                    // Создаются параметры изображения sourceName.min. и дефолтным расширением (webp)
                    $this->workerParams[] = $this->cloneParams()->setFilename($this->makeFileName());
                } else {
                    // Проверяем переданные вручную параметры создаем имена для файлов на основе переданных размеров
                    $this->makeNameBySize();
                }
            }

            if ($this->makeDefaultImage) {
                // Создаются параметры изображения sourceName.min. и расширением исходника
                $this->makeDefaultImageParams();
            }

            foreach ($this->workerParams as $imageParams) {
                $this->worker->params = $imageParams;
                $this->worker->execute();
            }

            if ($this->preload) {
                $this->addPreloadPageProperty();
            }

        } catch (Exception $e) {
            // Показываем ошибку только на деве и локально
            // Иначе кидаем в битрикс лог
            $this->hasError = true;
            if (in_array(Env::getName(), [Env::DEV, Env::LOC])) {
                ShowError($e->getMessage());
            } else {
                AddMessage2Log($e->getMessage(), "ImageWorker");
            }
        } finally {
            if ($this->contentMustReturned) {
                return $this->returnPictureHtml();
            }
            return true;
        }
    }

    /**
     * @return ImageParamsInterface
     */
    public function cloneParams(): ImageParamsInterface
    {
        return clone $this->worker->params;
    }

    private function prepare($sourceFileName, $options)
    {
        // Получаем данные по исходному файлу и передаем в настройки воркера
        $this->setRequiredParams($sourceFileName);
        // Для тех кто предпочитает передавать параметры массивом
        if (!empty($params)) {
            $this->parseOptionsArray($options);
        }
    }

    private function setRequiredParams($sourceFileName)
    {
        $this->fileInfo = pathinfo($sourceFileName);
        // Проверяем что передан не полный путь к файлу
        // Допустимо передать только название исходного изображения в таком случае будем смотреть в папке img
        if (strpos($sourceFileName, '/') !== false) {
            $siteDir = $this->fileInfo['dirname'] . '/';
            $output = $_SERVER['DOCUMENT_ROOT'] . $siteDir;
            $source = $output . $this->fileInfo['basename'];
            $this->src = $siteDir;
        } else {
            $siteDir = explode('?', Context::getCurrent()->getServer()->getRequestUri())[0]
                . 'img/'; // По умолчанию проверяем папку img
            $output = $_SERVER['DOCUMENT_ROOT']
                . $siteDir;
            $source = $output . $this->fileInfo['basename'];
            $this->src = 'img/';
        }
        $this->worker->params->output = $output;
        $this->worker->params->source = $source;
        $this->worker->params->filename = $this->fileInfo['filename'];
        $this->worker->params->extension = $this->defaultExtension;
        return $this;
    }

    private function makeOptionsByBreakpoints()
    {
        if (empty($this->customBreakpoints)) {
            $this->customBreakpoints = array_keys($this::BREAKPOINTS);
        }
        foreach ($this->customBreakpoints as $key) {
            $params = $this->cloneParams();
            $params->setWidth((int)$this::BREAKPOINTS[$key])
                ->setFilename($this->makeFileName($key));
            $this->workerParams[] = $params;
        }
    }

    private function makeDefaultImageParams()
    {
        $params = $this->cloneParams();
        $params->setFilename($this->makeFileName())
            ->setWidth(0)
            ->setExtension($this->fileInfo['extension']);
        $this->workerParams['default'] = $params;
    }

    private function makeFileName($postfix = ''): string
    {
        $filename = $this->fileInfo['filename'];
        if (!empty($postfix)) {
            $filename .= "__{$postfix}";
        }
        $filename .= ".min";
        return $filename;
    }

    private function makeNameBySize(): void
    {
        foreach ($this->workerParams as $param) {
            if (
                $this->fileInfo['filename'] == $param->filename
                && (!empty($param->width) || !empty($param->height))
            ) {
                $postfix = '.min.';
                if (!empty($param->width)) {
                    $postfix .= $param->width;
                }
                if (!empty($param->height)) {
                    $postfix .= "_$param->height";
                }
                $param->filename .= $postfix;
            } else {
                throw new Exception("Error: custom parameters dont has image size.");
            }
        }
    }

    private function addPreloadPageProperty()
    {
        $str = "<link rel=\"preload\" as=\"image\" href=\"{$this->src}{$this->fileInfo['basename']}\" imagesrcset=\"";
        $opts = '';
        foreach ($this->workerParams as $params) {
            $f = $this->src . $params->filename . '.' . $params->extension;
            $w = $params->width ? $params->width . 'w, ' : ', ';
            $opts .= "{$f} {$w}" ;
        }
        $opts = trim($opts, ', ');
        $str .= "{$opts}\">";
        Asset::getInstance()->addString($str, false, AssetLocation::BEFORE_CSS);
    }

    // Если преобразование произошло успешно, вернем картинку в теге <picture>
    // Иначе вернем тег img с исходной картинкой
    private function returnPictureHtml(): string
    {
        if (!$this->hasError) {
            return $this->makePictureHtml();
        } else {
            return $this->makeImgBySource();
        }
    }
    // Возвращаем тег <picture> с вложением преобразованных ресурсов
    private function makePictureHtml(): string
    {
        $picture = Html::openTag('picture');
        foreach ($this->workerParams as $key=>$params) {
            if ($key !== 'default') {
                $attrs = [
                    'srcset' => $this->src . $params->filename . '.' . $params->extension,
                    'type' => 'image/'. $params->extension,
                ];
                if ($params->width) {
                    $attrs['media'] = "(max-width: {$params->width}px)";
                }
                $picture .= Html::openTag('source', $attrs);
            }
        }
        if ($this->makeDefaultImage) {
            $option = $this->workerParams['default'];
            $filesize = getimagesize("{$option->output}{$option->filename}.{$option->extension}");
            $attrs = [
                'src' => "{$this->src}{$option->filename}.{$option->extension}",
                'alt' => "$option->filename.$option->extension",
                'width' => $filesize[0],
                'height' => $filesize[1],
            ];
            if (!empty($this->imgClasses)) {
                $attrs['class'] = implode(' ', $this->imgClasses);
            }
            if ($this->lazyload) {
                $attrs['loading'] = 'lazy';
            }
            $picture .= Html::openTag('img', $attrs);
        }
        $picture .= Html::closeTag('picture');
        return $picture;
    }

    // Возвращаем тег <img> с исходником
    private function makeImgBySource(): string
    {
        $imageParams = end($this->workerParams);
        $filesize = getimagesize("{$imageParams->source}");
        $attrs = [
            'src' => "{$this->src}{$this->fileInfo['basename']}",
            'alt' => "{$this->fileInfo['basename']}",
            'width' => $filesize[0],
            'height' => $filesize[1],
        ];
        if (!empty($this->imgClasses)) {
            $attrs['class'] = implode(' ', $this->imgClasses);
        }
        return Html::openTag('img', $attrs);
    }
}
