<?php

use PHPUnit\Framework\TestCase;

use Idleo\ImageOptimizer\Image;

class ImageTest extends TestCase
{
    public function testSum5and3()
    {
        $image = new Image();
        try {

            $test = Image::optimizeRaster('/resource/sectionHero.jpg');
            $test->setPreload(true);
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // $this->assertEquals(8, $image->sum(5, 3));
        // $this->assertNotEquals(8, $image->sum(2, 4));
    }
}