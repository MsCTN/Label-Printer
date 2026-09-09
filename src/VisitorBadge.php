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

        $this->width = intval($width);
        $this->height = intval($height);
        $this->data = $data;
    }

    public function render()
    {
        return $this->renderGraphics(true);
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

    protected function getLayout()
    {
        $scaleX = $this->width / 696;
        $scaleY = $this->height / 709;
        $scale = min($scaleX, $scaleY);
        $hasPhoto = $this->hasReadableImage('visitor_photo');

        $textX = $hasPhoto ? $this->scaledX(190) : $this->scaledX(36);
        $rightPadding = $this->scaledX(24);
        $maxTextWidth = max(1, $this->width - $textX - $rightPadding);
        $nameSize = $this->fitNativeSize($this->data['visitor_name'], $maxTextWidth, 83, 50);
        $companySize = $this->fitNativeSize($this->data['company_name'], $maxTextWidth, 58, 42);
        $hostSize = $this->fitNativeSize('Host: ' . $this->data['host_name'], $maxTextWidth, 58, 42);
        $validitySize = $this->fitNativeSize('Valid on: ' . $this->data['validity_date'], $maxTextWidth, 50, 38);

        return [
            'header' => [
                'x' => 0,
                'y' => $this->scaledY(34),
                'width' => $this->width,
                'height' => max(1, $this->scaledY(100)),
                'text' => 'VISITOR',
                'text_scale' => max(2, intval(round(5 * $scale)))
            ],
            'logo' => [
                'max_width' => $this->scaledX(92),
                'max_height' => $this->scaledY(82),
                'right' => $this->scaledX(22),
                'y' => $this->scaledY(43)
            ],
            'photo' => [
                'x' => $this->scaledX(24),
                'y' => $this->scaledY(170),
                'width' => $this->scaledX(142),
                'height' => $this->scaledY(170)
            ],
            'text_x' => $textX,
            'lines' => [
                'visitor_name' => [
                    'text' => $this->data['visitor_name'],
                    'y' => $this->scaledY(162),
                    'size' => $nameSize,
                    'preview_size' => $this->previewFontSize($nameSize),
                    'max_width' => $maxTextWidth,
                    'bold' => true
                ],
                'company_name' => [
                    'text' => $this->data['company_name'],
                    'y' => $this->scaledY(240),
                    'size' => $companySize,
                    'preview_size' => $this->previewFontSize($companySize),
                    'max_width' => $maxTextWidth,
                    'bold' => false
                ],
                'host_name' => [
                    'text' => 'Host: ' . $this->data['host_name'],
                    'y' => $this->scaledY(365),
                    'size' => $hostSize,
                    'preview_size' => $this->previewFontSize($hostSize),
                    'max_width' => $maxTextWidth,
                    'bold' => false
                ],
                'validity_date' => [
                    'text' => 'Valid on: ' . $this->data['validity_date'],
                    'y' => $this->scaledY(595),
                    'size' => $validitySize,
                    'preview_size' => $this->previewFontSize($validitySize),
                    'max_width' => $maxTextWidth,
                    'bold' => false
                ]
            ]
        ];
    }

    protected function scaledX($value)
    {
        return intval(round($value * ($this->width / 696)));
    }

    protected function scaledY($value)
    {
        return intval(round($value * ($this->height / 709)));
    }

    protected function outlineSize($baseSize)
    {
        $scale = min($this->width / 696, $this->height / 709);
        $target = $baseSize * $scale;
        $sizes = [33, 38, 42, 46, 50, 58, 67, 75, 83, 92, 100, 117, 133, 150, 167, 200];
        $selected = $sizes[0];

        foreach ($sizes as $size) {
            if (abs($size - $target) < abs($selected - $target)) {
                $selected = $size;
            }
        }

        return $selected;
    }

    protected function fitNativeSize($text, $maxWidth, $preferredSize, $minimumSize)
    {
        $preferred = $this->outlineSize($preferredSize);
        $sizes = [200, 167, 150, 133, 117, 100, 92, 83, 75, 67, 58, 50, 46, 42, 38, 33];
        $fallback = 33;

        foreach ($sizes as $size) {
            if ($size > $preferred || $size < $minimumSize) {
                continue;
            }

            if ($this->nativeTextWidth($text, $size) <= $maxWidth) {
                return $size;
            }

            $fallback = $size;
        }

        return $fallback;
    }

    protected function previewFontSize($nativeSize)
    {
        return max(18, intval(round($nativeSize * 0.68)));
    }

    protected function hasReadableImage($key)
    {
        return ! empty($this->data[$key]) && is_file($this->data[$key]);
    }

    protected function renderGraphics($includeText = false)
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('GD extension is required.');
        }

        $image = imagecreatetruecolor($this->width, $this->height);

        if ($image === false) {
            throw new \RuntimeException('Unable to create visitor badge image.');
        }

        $layout = $this->getLayout();
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);

        imagefill($image, 0, 0, $white);

        imagefilledrectangle(
            $image,
            $layout['header']['x'],
            $layout['header']['y'],
            $layout['header']['x'] + $layout['header']['width'] - 1,
            $layout['header']['y'] + $layout['header']['height'] - 1,
            $red
        );

        $this->drawHeaderText(
            $image,
            $layout['header'],
            $white,
            $red
        );

        if ($this->hasReadableImage('logo')) {
            $this->drawLogo($image, $layout['logo']);
        }

        if ($this->hasReadableImage('visitor_photo')) {
            $this->drawPhoto($image, $layout['photo']);
        }

        if ($includeText) {
            foreach ($layout['lines'] as $line) {
                $this->drawScaledText(
                    $image,
                    $layout['text_x'],
                    $line['y'],
                    $this->fitText($line['text'], $line['max_width'], $line['size']),
                    $line['preview_size'],
                    $line['max_width'],
                    $black,
                    $line['bold']
                );
            }
        }

        return $image;
    }

    protected function drawLogo($image, array $layout)
    {
        $logo = $this->loadImage($this->data['logo']);

        if ($logo === false) {
            return;
        }

        $sourceWidth = imagesx($logo);
        $sourceHeight = imagesy($logo);

        $scale = min(
            $layout['max_width'] / $sourceWidth,
            $layout['max_height'] / $sourceHeight
        );

        $logoWidth = max(1, intval($sourceWidth * $scale));
        $logoHeight = max(1, intval($sourceHeight * $scale));
        $logoX = $this->width - $logoWidth - $layout['right'];

        imagecopyresampled(
            $image,
            $logo,
            $logoX,
            $layout['y'],
            0,
            0,
            $logoWidth,
            $logoHeight,
            $sourceWidth,
            $sourceHeight
        );

        imagedestroy($logo);
    }

    protected function drawPhoto($image, array $layout)
    {
        $photo = $this->loadImage($this->data['visitor_photo']);

        if ($photo === false) {
            return;
        }

        imagecopyresampled(
            $image,
            $photo,
            $layout['x'],
            $layout['y'],
            0,
            0,
            $layout['width'],
            $layout['height'],
            imagesx($photo),
            imagesy($photo)
        );

        imagedestroy($photo);
    }

    protected function loadImage($path)
    {
        $imageData = file_get_contents($path);

        if ($imageData === false) {
            return false;
        }

        return @imagecreatefromstring($imageData);
    }

    protected function drawHeaderText($image, array $header, $color, $background)
    {
        $fontPath = $this->headerFontPath();

        if ($fontPath !== null && function_exists('imagettftext')) {
            $fontSize = max(18, intval($header['height'] * 0.54));

            while (
                $fontSize > 18 &&
                $this->trueTypeTextWidth($fontPath, $fontSize, $header['text']) > ($header['width'] * 0.48)
            ) {
                $fontSize--;
            }

            $box = imagettfbbox($fontSize, 0, $fontPath, $header['text']);
            $textWidth = abs($box[2] - $box[0]);
            $textHeight = abs($box[7] - $box[1]);
            $x = intval($header['x'] + (($header['width'] - $textWidth) / 2));
            $y = intval($header['y'] + (($header['height'] - $textHeight) / 2) + $textHeight);

            imagettftext(
                $image,
                $fontSize,
                0,
                $x,
                $y,
                $color,
                $fontPath,
                $header['text']
            );

            return;
        }

        $font = 5;
        $scale = $header['text_scale'];
        $sourceWidth = imagefontwidth($font) * strlen($header['text']);
        $sourceHeight = imagefontheight($font);
        $targetWidth = $sourceWidth * $scale;
        $targetHeight = $sourceHeight * $scale;

        $temp = imagecreatetruecolor($sourceWidth, $sourceHeight);

        imagefill($temp, 0, 0, $background);

        imagestring(
            $temp,
            $font,
            0,
            0,
            $header['text'],
            $color
        );

        $x = intval($header['x'] + (($header['width'] - $targetWidth) / 2));
        $y = intval($header['y'] + (($header['height'] - $targetHeight) / 2));

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

    protected function drawScaledText($image, $x, $y, $text, $fontSize, $maxWidth, $color, $bold = false)
    {
        $fontPath = $this->previewFontPath($bold);

        if ($fontPath !== null && function_exists('imagettftext')) {
            while ($fontSize > 12 && $this->trueTypeTextWidth($fontPath, $fontSize, $text) > $maxWidth) {
                $fontSize--;
            }

            imagettftext(
                $image,
                $fontSize,
                0,
                $x,
                $y + $fontSize,
                $color,
                $fontPath,
                $text
            );

            return;
        }

        $this->drawBitmapText($image, $x, $y, $text, max(1, intval($fontSize / 12)), $maxWidth);
    }

    protected function drawBitmapText($image, $x, $y, $text, $scale, $maxWidth)
    {
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);

        while (($textWidth * $scale) > $maxWidth && $scale > 1) {
            $scale--;
        }

        $temp = imagecreatetruecolor(max(1, $textWidth), $textHeight);
        $white = imagecolorallocate($temp, 255, 255, 255);
        $black = imagecolorallocate($temp, 0, 0, 0);

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

    protected function previewFontPath($bold = false)
    {
        $paths = $bold
            ? [
                'C:\\Windows\\Fonts\\arialbd.ttf',
                'C:\\Windows\\Fonts\\calibrib.ttf',
                'C:\\Windows\\Fonts\\georgiab.ttf'
            ]
            : [
                'C:\\Windows\\Fonts\\arial.ttf',
                'C:\\Windows\\Fonts\\calibri.ttf',
                'C:\\Windows\\Fonts\\georgia.ttf'
            ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function headerFontPath()
    {
        $paths = [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\calibrib.ttf',
            'C:\\Windows\\Fonts\\georgiab.ttf'
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function trueTypeTextWidth($fontPath, $fontSize, $text)
    {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);

        return abs($box[2] - $box[0]);
    }

    protected function fitText($text, $maxWidth, $size)
    {
        $text = (string) $text;

        while (strlen($text) > 1 && $this->nativeTextWidth($text, $size) > $maxWidth) {
            $text = substr($text, 0, -1);
        }

        return $text;
    }

    protected function nativeTextWidth($text, $size)
    {
        return strlen($text) * ($size * 0.42);
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

        $image = $this->renderGraphics(false);

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

            $layout = $this->getLayout();
            $bold = null;

            foreach ($layout['lines'] as $line) {
                if ($bold !== $line['bold']) {
                    $output .= (new Command\Bold($line['bold']))->read();
                    $bold = $line['bold'];
                }

                $output .= (
                    new Command\CharSize($line['size'], $font)
                )->read();

                $output .= (
                    new Command\AbsoluteHorizontalPosition($layout['text_x'])
                )->read();

                $output .= (
                    new Command\AbsoluteVerticalPosition($line['y'])
                )->read();

                $output .= $this->fitText(
                    $line['text'],
                    $line['max_width'],
                    $line['size']
                );
            }

            return $output;
        } finally {
            imagedestroy($image);
            @unlink($path);
        }
    }
}
