<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Exception;

class MaximalDepthWasReached extends \Graphpinator\Exception\GraphpinatorBase
{
    public const MESSAGE = 'Maximal fields depth %s was reached.';

    public function __construct(int $maxDepth)
    {
        parent::__construct([$maxDepth]);
    }

    public function isOutputable() : bool
    {
        return true;
    }
}
