<?php

namespace Talal\LabelPrinter\Command;

class Image implements CommandInterface
{
    protected $path;

    public function __construct($path)
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException('Image file does not exist.');
        }

        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required.');
        }

        $this->path = $path;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $imageData = file_get_contents($this->path);

        if ($imageData === false) {
            throw new \RuntimeException('Unable to read image file.');
        }

        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            throw new \InvalidArgumentException('Unsupported or invalid image file.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $output = chr(27) . '3' . chr(48);

        for ($stripTop = 0; $stripTop < $height; $stripTop += 48) {
            $data = '';

            for ($x = 0; $x < $width; $x++) {
                for ($byteIndex = 0; $byteIndex < 6; $byteIndex++) {
                    $byte = 0;

                    for ($bit = 0; $bit < 8; $bit++) {
                        $y = $stripTop + ($byteIndex * 8) + $bit;

                        if ($y >= $height) {
                            continue;
                        }

                        $rgb = imagecolorat($image, $x, $y);

                        $red = ($rgb >> 16) & 0xFF;
                        $green = ($rgb >> 8) & 0xFF;
                        $blue = $rgb & 0xFF;

                        $brightness = ($red + $green + $blue) / 3;

                        if ($brightness < 128) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }

                    $data .= chr($byte);
                }
            }

            $output .= (new BitImage(72, $width, $data))->read();

            if ($stripTop + 48 < $height) {
                $output .= chr(10);
            }
        }

        imagedestroy($image);

        return $output;
    }
}
