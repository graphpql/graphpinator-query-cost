<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Exception;

use Graphpinator\Exception\GraphpinatorBase;

class MaximalQueryCostWasReached extends GraphpinatorBase
{
    public const MESSAGE = 'Maximal query cost %s was reached.';

    public function __construct(
        int $maxQueryCost,
    )
    {
        parent::__construct([$maxQueryCost]);
    }

    #[\Override]
    public function isOutputable() : bool
    {
        return true;
    }
}
