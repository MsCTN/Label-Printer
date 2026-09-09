<?php

use Talal\LabelPrinter\Command;

class ImageTest extends PHPUnit_Framework_TestCase
{
    public function testInvalidPath()
    {
        $this->setExpectedException('InvalidArgumentException');

        new Command\Image('missing-image.png');
    }
    public function testInvalidImageData()
    {
        $path = tempnam(sys_get_temp_dir(), 'label-printer-');

        file_put_contents($path, 'not-an-image');

        $this->setExpectedException('InvalidArgumentException');

        try {
            $command = new Command\Image($path);
            $command->read();
        } finally {
            @unlink($path);
        }
    }
    public function testReadBlackPixel()
    {
        $path = tempnam(sys_get_temp_dir(), 'label-printer-');

        $image = imagecreatetruecolor(1, 1);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagesetpixel($image, 0, 0, $black);
        imagepng($image, $path);
        imagedestroy($image);

        $command = new Command\Image($path);

        $this->assertEquals(
            '1b33301b2a480100800000000000',
            bin2hex($command->read())
        );

        @unlink($path);
    }
    public function testReadMultipleStrips()
    {
        $path = tempnam(sys_get_temp_dir(), 'label-printer-');

        $image = imagecreatetruecolor(1, 49);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, 0, 48, $black);

        imagepng($image, $path);
        imagedestroy($image);

        $command = new Command\Image($path);

        $this->assertEquals(
            '1b33301b2a480100ffffffffffff0a1b2a480100800000000000',
            bin2hex($command->read())
        );

        @unlink($path);
    }

    public function testDitherUsesErrorDiffusion()
    {
        $path = tempnam(sys_get_temp_dir(), 'label-printer-');

        $image = imagecreatetruecolor(2, 1);
        $gray = imagecolorallocate($image, 120, 120, 120);

        imagesetpixel($image, 0, 0, $gray);
        imagesetpixel($image, 1, 0, $gray);
        imagepng($image, $path);
        imagedestroy($image);

        $withoutDither = new Command\Image($path, false);
        $withDither = new Command\Image($path, true);

        $this->assertEquals(
            '1b33301b2a480200800000000000800000000000',
            bin2hex($withoutDither->read())
        );

        $this->assertEquals(
            '1b33301b2a480200800000000000000000000000',
            bin2hex($withDither->read())
        );

        @unlink($path);
    }
}
