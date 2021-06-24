<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Exception;

class MaximalDepthWasReached extends \Graphpinator\Exception\GraphpinatorBase
{
    public const MESSAGE = 'Maximal fields depth was reached.';

    public function isOutputable() : bool
    {
        return true;
    }
}
