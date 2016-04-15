<?php

use Proengeno\Edifact\Message\Segments\SegFramework;

class SegmentDummy extends SegFramework
{
    protected static $validationBlueprint = [
        'A' => ['A' => 'M|an|3'],
        'B' => ['B' => 'O'],
        'C' => ['1' => 'M|an|3', '2' => 'M|an|3', '3' => 'O', '4' => 'M|an|3', '5' => 'M|an|3'],
        'D' => ['D' => 'O'],
        'E' => ['E' => 'O'],
        'F' => ['F' => 'O'],
    ];

    public static function fromAttributes($attribute)
    {
        return new static([
            'A' => ['A' => $attribute],
        ]);
    }
}
    
