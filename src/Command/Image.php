<?php

namespace Talal\LabelPrinter\Command;

class Image implements CommandInterface
{
    protected $path;

    protected $dither;

    public function __construct($path, $dither = false)
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException(
                'Image file does not exist.'
            );
        }

        if (! extension_loaded('gd')) {
            throw new \RuntimeException(
                'GD extension is required.'
            );
        }

        $this->path = $path;
        $this->dither = (bool) $dither;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $imageData = file_get_contents($this->path);

        if ($imageData === false) {
            throw new \RuntimeException(
                'Unable to read image file.'
            );
        }

        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            throw new \InvalidArgumentException(
                'Unsupported or invalid image file.'
            );
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

                        if ($this->isBlackPixel($image, $x, $y)) {
                            $byte |= (1 << (7 - $bit));
                        }
                    }

                    $data .= chr($byte);
                }
            }

            $output .= (
                new BitImage(72, $width, $data)
            )->read();

            if ($stripTop + 48 < $height) {
                $output .= chr(10);
            }
        }

        imagedestroy($image);

        return $output;
    }

    protected function isBlackPixel($image, $x, $y)
    {
        $rgb = imagecolorat($image, $x, $y);

        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;

        /*
         * Weighted luminance gives a more accurate grayscale
         * representation than simply averaging RGB.
         */
        $brightness = (
            ($red * 0.299) +
            ($green * 0.587) +
            ($blue * 0.114)
        );

        if (! $this->dither) {
            return $brightness < 128;
        }

        /*
         * 4x4 Bayer ordered dithering matrix.
         * Helps preserve facial detail and grayscale tones
         * when printing on monochrome thermal media.
         */
        $matrix = [
            [0, 8, 2, 10],
            [12, 4, 14, 6],
            [3, 11, 1, 9],
            [15, 7, 13, 5]
        ];

        $threshold = (
            ($matrix[$y % 4][$x % 4] + 0.5) / 16
        ) * 255;

        return $brightness < $threshold;
    }
}