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

        $pixels = $this->dither
            ? $this->createDitheredPixelMap($image)
            : null;

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

                        if ($this->isBlackPixel($image, $x, $y, $pixels)) {
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

    protected function isBlackPixel($image, $x, $y, array $pixels = null)
    {
        if ($pixels !== null) {
            return ! empty($pixels[$y][$x]);
        }

        return $this->brightnessAt($image, $x, $y) < 128;
    }

    protected function createDitheredPixelMap($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $values = [];
        $pixels = [];

        for ($y = 0; $y < $height; $y++) {
            $values[$y] = [];

            for ($x = 0; $x < $width; $x++) {
                $values[$y][$x] = $this->brightnessAt($image, $x, $y);
            }
        }

        /*
         * Floyd-Steinberg diffusion keeps photographic midtones more legible
         * than an ordered matrix while still producing a 1-bit ESC/P payload.
         */
        for ($y = 0; $y < $height; $y++) {
            $pixels[$y] = [];

            for ($x = 0; $x < $width; $x++) {
                $old = $values[$y][$x];
                $new = $old < 128 ? 0 : 255;
                $pixels[$y][$x] = ($new === 0);

                $error = $old - $new;

                $this->diffuseError($values, $width, $height, $x + 1, $y, $error, 7 / 16);
                $this->diffuseError($values, $width, $height, $x - 1, $y + 1, $error, 3 / 16);
                $this->diffuseError($values, $width, $height, $x, $y + 1, $error, 5 / 16);
                $this->diffuseError($values, $width, $height, $x + 1, $y + 1, $error, 1 / 16);
            }
        }

        return $pixels;
    }

    protected function diffuseError(array &$values, $width, $height, $x, $y, $error, $factor)
    {
        if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
            return;
        }

        $values[$y][$x] += $error * $factor;

        if ($values[$y][$x] < 0) {
            $values[$y][$x] = 0;
        } elseif ($values[$y][$x] > 255) {
            $values[$y][$x] = 255;
        }
    }

    protected function brightnessAt($image, $x, $y)
    {
        $rgb = imagecolorat($image, $x, $y);

        if (! imageistruecolor($image)) {
            $colors = imagecolorsforindex($image, $rgb);
            $red = $colors['red'];
            $green = $colors['green'];
            $blue = $colors['blue'];
        } else {
            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;
        }

        /*
         * Weighted luminance gives a more accurate grayscale
         * representation than simply averaging RGB.
         */
        return (
            ($red * 0.299) +
            ($green * 0.587) +
            ($blue * 0.114)
        );
    }
}
