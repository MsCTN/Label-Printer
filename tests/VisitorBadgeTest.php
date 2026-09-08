<?php

use Talal\LabelPrinter\VisitorBadge;

class VisitorBadgeTest extends PHPUnit_Framework_TestCase
{
    public function testRenderWithoutPhoto()
    {
        $badge = new VisitorBadge(
            400,
            200,
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

        imagedestroy($image);
    }
    public function testRenderWithPhoto()
    {
        $photoPath = __DIR__ . '/fixtures/visitor-photo.png';

        $badge = new VisitorBadge(
            400,
            200,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '26/10/2021',
                'host_name' => 'John Smith',
                'visitor_photo' => $photoPath,
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $image = $badge->render();

        $this->assertTrue(is_resource($image) || is_object($image));

        imagedestroy($image);
    }
    public function testPrintThroughEscp()
    {
        $stream = fopen('php://temp', 'w+');

        $mode = new \Talal\LabelPrinter\Mode\Escp($stream);
        $printer = new \Talal\LabelPrinter\Printer($mode);

        $badge = new VisitorBadge(
            400,
            200,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '26/10/2021',
                'host_name' => 'John Smith',
                'visitor_photo' => __DIR__ . '/fixtures/visitor-photo.png',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $printer->addCommand($badge)->printLabel();

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals(12039, strlen($output));
        $this->assertEquals(
            '1b6961301b401b33301b2a489001',
            bin2hex(substr($output, 0, 14))
        );
        $this->assertEquals(chr(12), substr($output, -1));
    }
    public function testPrintWithoutPhotoThroughEscp()
    {
        $stream = fopen('php://temp', 'w+');

        $mode = new \Talal\LabelPrinter\Mode\Escp($stream);
        $printer = new \Talal\LabelPrinter\Printer($mode);

        $badge = new VisitorBadge(
            400,
            200,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '26/10/2021',
                'host_name' => 'John Smith',
                'logo' => __DIR__ . '/fixtures/institution-logo.png'
            ]
        );

        $printer->addCommand($badge)->printLabel();

        rewind($stream);
        $output = stream_get_contents($stream);
        fclose($stream);

        $this->assertEquals(12039, strlen($output));
        $this->assertEquals(
            '1b6961301b401b33301b2a489001',
            bin2hex(substr($output, 0, 14))
        );
        $this->assertEquals(chr(12), substr($output, -1));
    }
}
