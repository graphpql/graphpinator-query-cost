<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Exception;

use Graphpinator\Exception\GraphpinatorBase;

class MaximalDepthWasReached extends GraphpinatorBase
{
    public const MESSAGE = 'Maximal fields depth %s was reached.';

    public function __construct(
        int $maxDepth,
    )
    {
        parent::__construct([$maxDepth]);
    }

    #[\Override]
    public function isOutputable() : bool
    {
        return true;
    }
}
