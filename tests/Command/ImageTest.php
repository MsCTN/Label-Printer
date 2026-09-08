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
}
