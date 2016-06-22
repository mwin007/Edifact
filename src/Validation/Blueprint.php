<?php

namespace Proengeno\Edifact\Validation;

use Proengeno\Edifact\Exceptions\ValidationException;

class Blueprint
{
    protected $blueprint = [];
    protected $loopIndex = 0;
    protected $blueprintCount = [];

    private $loopIsNecessary = true;
    
    public function __construct($blueprint)
    {
        $this->flattenBlueprint($blueprint, 0);
    }

    public function validate($segment)
    {
        if ($this->unnecessarySegmentIsMissing($segment)) {
            $this->countUpBlueprint();
        }
        if ($this->unnecessaryLoopIsMissing($segment)) {
            $this->countUpBlueprint();
        }

        if ($this->startOfLoop()) {
            $this->nextLoop();
        } elseif ($this->startOfReLoop($segment)) {
            $this->reLoop();
        } else if ($this->endOfLoop($segment)) {
            $this->previosLoop();
        }

        if ($segment->name() == $this->getBlueprintAttribute('name')) {
            $this->loopIsNecessary = true;
            $this->countUpBlueprint();
            return;
        }

        throw ValidationException::unexpectedSegment('', @$segment->name(), $this->getBlueprintAttribute('name'));
    }

    private function unnecessaryLoopIsMissing($segment)
    {
        return $this->getBlueprintAttribute('name') == 'LOOP'
            && $segment->name() == $this->getBlueprintAttribute('name', $this->getBlueprintCount() + 1)
            && $this->getBlueprintAttribute('necessity') == 'O';
    }

    private function unnecessarySegmentIsMissing($segment)
    {
        return $this->getBlueprintAttribute('necessity') == 'O' && $segment->name() != $this->getBlueprintAttribute('name');
    }

    private function startOfLoop()
    {
        return $this->getBlueprintAttribute('name') == 'LOOP';
    }

    private function reLoop()
    {
        $this->blueprintCount[$this->loopIndex] = 0;
    }

    private function startOfReLoop($segment)
    {
        if ($this->endOfLoop() && $segment->name() == $this->getBlueprintAttribute('name', 0)) {
            return true;
        }
        return false;
    }

    private function nextLoop()
    {
        $this->loopIndex++;
        $this->blueprintCount[$this->loopIndex] = 0;
    }

    private function endOfLoop()
    {
        return $this->getBlueprintCount() >= count($this->blueprint[$this->loopIndex]);
    }

    private function previosLoop()
    {
        $this->loopIndex--;
        $this->countUpBlueprint();
    }

    private function flattenBlueprint($blueprint, $index)
    {
        foreach ($blueprint as $blueprintRow) {
            if (isset($blueprintRow['segments'])) {
                $this->flattenBlueprint($blueprintRow['segments'], $index + 1);
            }
            $this->blueprint[$index][] = $blueprintRow;
        }
    }

    private function getBlueprintAttribute($attribute, $blueprintCount = null)
    {
        if ($blueprintCount === null) {
            $blueprintCount = $this->getBlueprintCount();
        }
        if (isset($this->blueprint[$this->loopIndex][$blueprintCount][$attribute])) {
            return $this->blueprint[$this->loopIndex][$blueprintCount][$attribute];
        }        
        return null;
    }

    private function getBlueprintCount()
    {
        if (!isset($this->blueprintCount[$this->loopIndex])) {
            $this->blueprintCount[$this->loopIndex] = 0;
        }
        return $this->blueprintCount[$this->loopIndex];
    }

    private function countUpBlueprint()
    {
        if (!isset($this->blueprintCount[$this->loopIndex])) {
            $this->blueprintCount[$this->loopIndex] = 1;
        }
        $this->blueprintCount[$this->loopIndex] ++;
    }
}
