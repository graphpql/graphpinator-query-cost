<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Exception;

class MaximalQueryCostWasReached extends \Graphpinator\Exception\GraphpinatorBase
{
    public const MESSAGE = 'Maximal query cost %s was reached.';

    public function __construct(int $maxQueryCost)
    {
        $this->messageArgs = [$maxQueryCost];

        parent::__construct();
    }

    public function isOutputable() : bool
    {
        return true;
    }
}
