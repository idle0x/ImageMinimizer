<?php


namespace Idleo\ImageOptimizer;


use Exception;

abstract class BaseImg
{
    // Объект вызывающий переделку картинки в соответствии с переданными ему параметрами
    protected BaseWorker $worker;

    protected string $defaultExtension;
    // Директория в которой будет изображение
    protected string $src;
    // Информация об исходном файле
    protected array $fileInfo = [];
    // Набор параметров под вариант модификации изображений
    // В методе execute воркер запускает модификацию в соответствии с каждым из вариантов
    protected array $workerParams = [];
    /** @var bool If worker excepted return source image */
    protected bool $hasError = false;
    /** @var bool Return content (html markup or svg source) */
    protected bool $contentMustReturned = true;

    abstract public function run();

    public function isOptimizationCorrect(): bool
    {
        $count = 0;
        foreach ($this->workerParams as $params) {
            $filePath = $params->output . $params->filename . '.' . $params->extension;
            if (file_exists($filePath)) {
                $count++;
            }
        }
        return count($this->workerParams) == $count;
    }

    /**
     * Return Html or svg source
     * @param bool $returnContent
     * @return $this
     */
    public function returnContent(bool $returnContent)
    {
        $this->contentMustReturned = $returnContent;
        return $this;
    }
}