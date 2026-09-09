<?php

namespace Talal\LabelPrinter;

use Talal\LabelPrinter\Command;
use Talal\LabelPrinter\Command\CommandInterface;

class VisitorBadge implements CommandInterface
{
    protected $width;

    protected $height;

    protected $data;

    protected $requiredFields = [
        'visitor_name',
        'company_name',
        'validity_date',
        'host_name'
    ];

    public function __construct($width, $height, array $data)
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Badge width and height must be greater than zero.');
        }

        foreach ($this->requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new \InvalidArgumentException(
                    'Missing required visitor badge field: ' . $field
                );
            }
        }

        $this->width = $width;
        $this->height = $height;
        $this->data = $data;
    }


    public function render()
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required.');
        }

        $image = imagecreatetruecolor($this->width, $this->height);

        if ($image === false) {
            throw new \RuntimeException('Unable to create visitor badge image.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagefill($image, 0, 0, $white);

        /*
         * Header
         */
        $headerHeight = 50;

        imagefilledrectangle(
            $image,
            0,
            0,
            $this->width - 1,
            $headerHeight - 1,
            $red
        );

        $this->drawHeaderText(
            $image,
            'VISITOR',
            $headerHeight
        );

        /*
         * Institution logo
         */
        if (! empty($this->data['logo']) && is_file($this->data['logo'])) {
            $logoData = file_get_contents($this->data['logo']);
            $logo = @imagecreatefromstring($logoData);

            if ($logo !== false) {
                $sourceWidth = imagesx($logo);
                $sourceHeight = imagesy($logo);

                $maxLogoWidth = 72;
                $maxLogoHeight = 62;

                $scale = min(
                    $maxLogoWidth / $sourceWidth,
                    $maxLogoHeight / $sourceHeight
                );

                $logoWidth = intval($sourceWidth * $scale);
                $logoHeight = intval($sourceHeight * $scale);

                $logoX = $this->width - $logoWidth - 12;

                /*
         * Slightly overlap the logo into the badge body.
         * Header height is currently 50px.
         */
                $logoY = 8;

                imagecopyresampled(
                    $image,
                    $logo,
                    $logoX,
                    $logoY,
                    0,
                    0,
                    $logoWidth,
                    $logoHeight,
                    $sourceWidth,
                    $sourceHeight
                );

                imagedestroy($logo);
            }
        }

        /*
         * Main badge content
         */
        $contentTop = 65;
        $textX = 20;

        /*
         * Optional visitor photo
         */
        if (
            ! empty($this->data['visitor_photo']) &&
            is_file($this->data['visitor_photo'])
        ) {

            $photoData = file_get_contents($this->data['visitor_photo']);
            $photo = @imagecreatefromstring($photoData);

            if ($photo !== false) {
                $photoWidth = 120;
                $photoHeight = 120;

                imagecopyresampled(
                    $image,
                    $photo,
                    20,
                    $contentTop,
                    0,
                    0,
                    $photoWidth,
                    $photoHeight,
                    imagesx($photo),
                    imagesy($photo)
                );

                imagedestroy($photo);

                $textX = 160;
            }
        }

        /*
         * Visitor details
         */
        $this->drawScaledText(
            $image,
            $textX,
            $contentTop,
            $this->data['visitor_name'],
            2
        );

        $this->drawScaledText(
            $image,
            $textX,
            $contentTop + 35,
            $this->data['company_name'],
            1
        );

        $this->drawScaledText(
            $image,
            $textX,
            $contentTop + 70,
            'Host: ' . $this->data['host_name'],
            1
        );

        /*
         * Validity date
         */
        $this->drawScaledText(
            $image,
            $textX,
            $this->height - 30,
            'Valid on: ' . $this->data['validity_date'],
            1
        );

        return $image;
    }

    protected function drawHeaderText($image, $text, $headerHeight)
    {
        $font = 5;
        $scale = 2;

        $sourceWidth = imagefontwidth($font) * strlen($text);
        $sourceHeight = imagefontheight($font);

        $targetWidth = $sourceWidth * $scale;
        $targetHeight = $sourceHeight * $scale;

        $temp = imagecreatetruecolor(
            $sourceWidth,
            $sourceHeight
        );

        $black = imagecolorallocate($temp, 0, 0, 0);
        $white = imagecolorallocate($temp, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagefill($temp, 0, 0, $red);

        imagestring(
            $temp,
            $font,
            0,
            0,
            $text,
            $white
        );

        $x = intval(($this->width - $targetWidth) / 2);
        $y = intval(($headerHeight - $targetHeight) / 2);

        imagecopyresized(
            $image,
            $temp,
            $x,
            $y,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        imagedestroy($temp);
    }

    protected function drawText($image, $x, $y, $text)
    {
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagestring(
            $image,
            5,
            $x,
            $y,
            $text,
            $black
        );
    }

    protected function drawScaledText($image, $x, $y, $text, $scale = 2)
    {
        $font = 5;

        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);

        $availableWidth = $this->width - $x - 10;

        while (($textWidth * $scale) > $availableWidth && $scale > 1) {
            $scale--;
        }

        $temp = imagecreatetruecolor(
            $textWidth,
            $textHeight
        );

        $white = imagecolorallocate($temp, 255, 255, 255);
        $black = imagecolorallocate($temp, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagefill($temp, 0, 0, $white);

        imagestring(
            $temp,
            $font,
            0,
            0,
            $text,
            $black
        );

        imagecopyresized(
            $image,
            $temp,
            $x,
            $y,
            0,
            0,
            $textWidth * $scale,
            $textHeight * $scale,
            $textWidth,
            $textHeight
        );

        imagedestroy($temp);
    }

    public function save($path)
    {
        $image = $this->render();

        if (! imagepng($image, $path)) {
            imagedestroy($image);

            throw new \RuntimeException(
                'Unable to save visitor badge image.'
            );
        }

        imagedestroy($image);

        return $path;
    }
    protected function renderGraphics()
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required.');
        }

        $image = imagecreatetruecolor($this->width, $this->height);

        if ($image === false) {
            throw new \RuntimeException('Unable to create visitor badge image.');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagefill($image, 0, 0, $white);

        $headerHeight = 50;

        imagefilledrectangle(
            $image,
            0,
            0,
            $this->width - 1,
            $headerHeight - 1,
            $red
        );

        $this->drawHeaderText(
            $image,
            'VISITOR',
            $headerHeight
        );

        if (! empty($this->data['logo']) && is_file($this->data['logo'])) {
            $logoData = file_get_contents($this->data['logo']);
            $logo = @imagecreatefromstring($logoData);

            if ($logo !== false) {
                $sourceWidth = imagesx($logo);
                $sourceHeight = imagesy($logo);

                $maxLogoWidth = 72;
                $maxLogoHeight = 62;

                $scale = min(
                    $maxLogoWidth / $sourceWidth,
                    $maxLogoHeight / $sourceHeight
                );

                $logoWidth = intval($sourceWidth * $scale);
                $logoHeight = intval($sourceHeight * $scale);

                $logoX = $this->width - $logoWidth - 12;
                $logoY = 8;

                imagecopyresampled(
                    $image,
                    $logo,
                    $logoX,
                    $logoY,
                    0,
                    0,
                    $logoWidth,
                    $logoHeight,
                    $sourceWidth,
                    $sourceHeight
                );

                imagedestroy($logo);
            }
        }

        if (
            ! empty($this->data['visitor_photo']) &&
            is_file($this->data['visitor_photo'])
        ) {
            $photoData = file_get_contents($this->data['visitor_photo']);
            $photo = @imagecreatefromstring($photoData);

            if ($photo !== false) {
                imagecopyresampled(
                    $image,
                    $photo,
                    20,
                    65,
                    0,
                    0,
                    120,
                    120,
                    imagesx($photo),
                    imagesy($photo)
                );

                imagedestroy($photo);
            }
        }

        return $image;
    }
    public function read()
    {
        $path = tempnam(
            sys_get_temp_dir(),
            'visitor-badge-'
        );

        if ($path === false) {
            throw new \RuntimeException(
                'Unable to create temporary visitor badge file.'
            );
        }

        $image = $this->renderGraphics();

        try {
            if (! imagepng($image, $path)) {
                throw new \RuntimeException(
                    'Unable to save visitor badge image.'
                );
            }

            $output = (new Command\Image($path, true))->read();

            $font = new Command\Font(
                'brussels',
                Command\Font::TYPE_OUTLINE
            );

            $output .= $font->read();

            $textX = 20;

            if (
                ! empty($this->data['visitor_photo']) &&
                is_file($this->data['visitor_photo'])
            ) {
                $textX = 160;
            }

            $output .= (
                new Command\AbsoluteHorizontalPosition($textX)
            )->read();

            $output .= (
                new Command\AbsoluteVerticalPosition(65)
            )->read();

            $output .= $this->data['visitor_name'];

            $output .= (
                new Command\AbsoluteHorizontalPosition($textX)
            )->read();

            $output .= (
                new Command\AbsoluteVerticalPosition(100)
            )->read();

            $output .= $this->data['company_name'];

            $output .= (
                new Command\AbsoluteHorizontalPosition($textX)
            )->read();

            $output .= (
                new Command\AbsoluteVerticalPosition(135)
            )->read();

            $output .= 'Host: ' . $this->data['host_name'];

            $output .= (
                new Command\AbsoluteHorizontalPosition($textX)
            )->read();

            $output .= (
                new Command\AbsoluteVerticalPosition(
                    $this->height - 30
                )
            )->read();

            $output .= 'Valid on: ' . $this->data['validity_date'];

            return $output;
        } finally {
            imagedestroy($image);
            @unlink($path);
        }
    }
}
