<?php

use Talal\LabelPrinter\Command;

class BitImageTest extends PHPUnit_Framework_TestCase
{
  public function testRead()
  {
    $data =
      chr(128) .
      chr(0) .
      chr(0) .
      chr(0) .
      chr(0) .
      chr(0);

    $command = new Command\BitImage(72, 1, $data);

    $this->assertEquals(
      '1b2a480100800000000000',
      bin2hex($command->read())
    );
  }

  public function testInvalidMode()
  {
    $this->setExpectedException('InvalidArgumentException');

    new Command\BitImage(99, 1, str_repeat(chr(0), 6));
  }

  /**
   * @expectedException OutOfRangeException
   */
  public function testInvalidDotPositions()
  {
    $command = new Command\BitImage(72, 3072, str_repeat(chr(0), 3072 * 6));

    $command->read();
  }
  public function testInvalidDataLength()
  {
    $this->setExpectedException('InvalidArgumentException');

    new Command\BitImage(72, 1, str_repeat(chr(0), 5));
  }

  /**
   * @expectedException OutOfRangeException
   */
  public function testNegativeDotPositions()
  {
    new Command\BitImage(72, -1, '');
  }
}
