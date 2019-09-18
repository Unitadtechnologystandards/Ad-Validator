<?php
declare(strict_types=1);

namespace OVKAC\State;

final class DefaultStateData implements StateData
{
    private $parameters;
    private $results;
    private $created;

    public function __construct()
    {
        $this->created = new \DateTime;
        $this->parameters = new DefaultStateParameters;
        $this->results = new DefaultStateResults;
    }

    public function parameters(): StateParameters
    {
        return $this->parameters;
    }

    public function results(): StateResults
    {
        return $this->results;
    }

    public function jsonSerialize()
    {
        return [
            'created' => $this->created,
            'parameters' => $this->parameters,
            'results' => $this->results
        ];
    }
}