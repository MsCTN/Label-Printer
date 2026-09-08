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
                'host_name' => 'Jane Smith'
            ]
        );

        $image = $badge->render();

        $this->assertTrue(is_resource($image) || is_object($image));

        imagedestroy($image);
    }
    public function testRenderWithPhoto()
    {
        $photoPath = __DIR__ . '/fixtures/visitor-photo.png';
        echo $photoPath;
        $badge = new VisitorBadge(
            400,
            200,
            [
                'visitor_name' => 'Stuart Burgess',
                'company_name' => 'A&D Buildings Ltd',
                'validity_date' => '26/10/2021',
                'host_name' => 'John Smith',
                'visitor_photo' => $photoPath
            ]
        );

        $image = $badge->render();

        $this->assertTrue(is_resource($image) || is_object($image));

        imagedestroy($image);
    }
}
