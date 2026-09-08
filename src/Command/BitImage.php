<?php

namespace Talal\LabelPrinter\Command;

use InvalidArgumentException;

class BitImage implements CommandInterface
{
    /**
     * Brother ESC/P Command Reference
     *
     * https://download.brother.com/welcome/docp000706/cv_ql720_eng_escp_100.pdf
     *
     * Graphics commands -> Select bit image
     *
     * ASCII: ESC * m n1 n2 Data
     */

    protected $mode;

    protected $dotPositions;

    protected $data;

    public function __construct($mode, $dotPositions, $data)
    {

        $validModes = [0, 1, 2, 3, 4, 6, 32, 33, 38, 39, 40, 71, 72, 73];

        if (! in_array($mode, $validModes)) {
            throw new InvalidArgumentException('Please provide a valid bit image mode.');
        }

        if ($dotPositions < 0 || $dotPositions > 3071) {
            throw new \OutOfRangeException('Dot positions must be between 0 and 3071.');
        }

        if (in_array($mode, [0, 1, 2, 3, 4, 6])) {
            $bytesPerDotPosition = 1;
        } elseif (in_array($mode, [32, 33, 38, 39, 40])) {
            $bytesPerDotPosition = 3;
        } else {
            $bytesPerDotPosition = 6;
        }

        $expectedLength = $dotPositions * $bytesPerDotPosition;

        if (strlen($data) !== $expectedLength) {
            throw new InvalidArgumentException('Invalid bit image data length.');
        }

        $this->mode = $mode;
        $this->dotPositions = $dotPositions;
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {

        $n1 = $this->dotPositions % 256;
        $n2 = intval($this->dotPositions / 256);

        return
            chr(27) .
            '*' .
            chr($this->mode) .
            chr($n1) .
            chr($n2) .
            $this->data;
    }
}
