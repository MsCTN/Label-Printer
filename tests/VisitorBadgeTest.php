<?php

use Talal\LabelPrinter\VisitorBadge;

class VisitorBadgeTest extends PHPUnit_Framework_TestCase
{
    public function testRenderWithoutPhoto()
    {
        $badge = new VisitorBadge(
            696,
            590,
            [
                'visitor_name' => 'John Doe',
                'company_name' => 'Example Ltd',
                'validity_date' => '2026-09-08',
                'host_name' => 'Jane Smith',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $image = $badge->render();

        $this->assertTrue(is_resource($image) || is_object($image));
        $this->assertEquals(696, imagesx($image));
        $this->assertEquals(590, imagesy($image));

        imagedestroy($image);
    }

    public function testRenderWithPhoto()
    {
        $photoPath = __DIR__ . '/fixtures/visitor-photo.png';

        $badge = new VisitorBadge(
            696,
            590,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '09 Sept 2026',
                'host_name' => 'John Smith',
                'visitor_photo' => $photoPath,
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $image = $badge->render();

        $this->assertTrue(is_resource($image) || is_object($image));
        $this->assertEquals(696, imagesx($image));
        $this->assertEquals(590, imagesy($image));

        imagedestroy($image);
    }

    public function testRequiredFields()
    {
        $this->setExpectedException('InvalidArgumentException');

        new VisitorBadge(
            696,
            590,
            [
                'visitor_name' => 'John Doe',
                'company_name' => 'Example Ltd',
                'validity_date' => '2026-09-08'
            ]
        );
    }

    public function testPrintThroughEscp()
    {
        $stream = fopen('php://temp', 'w+');

        $mode = new \Talal\LabelPrinter\Mode\Escp($stream);
        $printer = new \Talal\LabelPrinter\Printer($mode);

        $badge = new VisitorBadge(
            696,
            590,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '09 Sept 2026',
                'host_name' => 'John Smith',
                'visitor_photo' => __DIR__ . '/fixtures/visitor-photo.png',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $printer->addCommand($badge)->printLabel();

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals(109787, strlen($output));
        $this->assertEquals(
            '1b6961301b401b6961011b401b69531b697a8e0a3e004e02000000001b694d401b6941011b694b091b6964230077',
            bin2hex(substr($output, 0, 46))
        );
        $this->assertContains(chr(27) . 'ia' . chr(1), $output);
        $this->assertContains(chr(27) . 'iK' . chr(9), $output);
        $this->assertEquals(590, substr_count($output, 'w' . chr(1) . chr(90)));
        $this->assertEquals(590, substr_count($output, 'w' . chr(2) . chr(90)));
        $this->assertEquals(chr(26), substr($output, -2, 1));
        $this->assertEquals(chr(12), substr($output, -1));
    }

    public function testPrintWithoutPhotoThroughEscp()
    {
        $stream = fopen('php://temp', 'w+');

        $mode = new \Talal\LabelPrinter\Mode\Escp($stream);
        $printer = new \Talal\LabelPrinter\Printer($mode);

        $badge = new VisitorBadge(
            696,
            590,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '09 Sept 2026',
                'host_name' => 'John Smith',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $printer->addCommand($badge)->printLabel();

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals(109787, strlen($output));
        $this->assertContains(chr(27) . 'ia' . chr(1), $output);
        $this->assertContains(chr(27) . 'iK' . chr(9), $output);
        $this->assertEquals(590, substr_count($output, 'w' . chr(1) . chr(90)));
        $this->assertEquals(590, substr_count($output, 'w' . chr(2) . chr(90)));
        $this->assertEquals(chr(26), substr($output, -2, 1));
        $this->assertEquals(chr(12), substr($output, -1));
    }

    public function testPrintUsesRequestedHeightForRasterRows()
    {
        $badge = new VisitorBadge(
            696,
            509,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '09 Sept 2026',
                'host_name' => 'John Smith',
                'visitor_photo' => __DIR__ . '/fixtures/visitor-photo.png',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $output = $badge->read();

        $this->assertEquals(94714, strlen($output));
        $this->assertContains(chr(27) . 'ia' . chr(1), $output);
        $this->assertContains(chr(27) . 'iK' . chr(9), $output);
        $this->assertEquals(509, substr_count($output, 'w' . chr(1) . chr(90)));
        $this->assertEquals(509, substr_count($output, 'w' . chr(2) . chr(90)));
        $this->assertEquals(chr(26), substr($output, -1));
    }
}
