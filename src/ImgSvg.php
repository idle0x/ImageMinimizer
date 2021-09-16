<?php

namespace Idleo\ImageOptimizer;


class ImgSvg
{
    private bool $lazyloadImage;

    public function setLazyload($lazyloadImage)
    {
        $this->lazyloadImage = $lazyloadImage;
        return $this;
    }
}