<?php

namespace Talal\LabelPrinter\Command;

class AbsoluteHorizontalPosition implements CommandInterface
{
    protected $position;

    public function __construct($position)
    {
        if ($position < 0 || $position > 32767) {
            throw new \OutOfRangeException(
                'Horizontal position must be between 0 and 32767.'
            );
        }

        $this->position = intval($position);
    }

    public function read()
    {
        $mL = $this->position % 256;
        $mH = intval($this->position / 256);

        return chr(27) .
            '$' .
            chr($mL) .
            chr($mH);
    }
}