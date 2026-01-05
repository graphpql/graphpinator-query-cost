<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

use Graphpinator\Module\Module;
use Graphpinator\Normalizer\FinalizedRequest;
use Graphpinator\Normalizer\NormalizedRequest;
use Graphpinator\Normalizer\Selection\Field;
use Graphpinator\Normalizer\Selection\FragmentSpread;
use Graphpinator\Normalizer\Selection\InlineFragment;
use Graphpinator\Normalizer\Selection\SelectionSet;
use Graphpinator\Parser\ParsedRequest;
use Graphpinator\QueryCost\Exception\MaximalDepthWasReached;
use Graphpinator\Request\Request;
use Graphpinator\Resolver\Result;

final class MaxDepthModule implements Module
{
    public function __construct(
        private int $maxDepth,
    )
    {
    }

    #[\Override]
    public function processRequest(Request $request) : Request
    {
        return $request;
    }

    #[\Override]
    public function processParsed(ParsedRequest $request) : ParsedRequest
    {
        return $request;
    }

    #[\Override]
    public function processNormalized(NormalizedRequest $request) : NormalizedRequest
    {
        foreach ($request->operations as $operation) {
            $this->validateDepth(1, $operation->children);
        }

        return $request;
    }

    #[\Override]
    public function processFinalized(FinalizedRequest $request) : FinalizedRequest
    {
        return $request;
    }

    #[\Override]
    public function processResult(Result $result) : Result
    {
        return $result;
    }

    private function validateDepth(int $fieldDepth, SelectionSet $selectionSet) : void
    {
        if ($fieldDepth > $this->maxDepth) {
            throw new MaximalDepthWasReached($this->maxDepth);
        }

        foreach ($selectionSet as $selection) {
            switch ($selection::class) {
                case Field::class:
                    $currentFieldSet = $selection->children;

                    if ($currentFieldSet === null) {
                        continue 2;
                    }

                    $nextDepth = $fieldDepth + 1;
                    $this->validateDepth($nextDepth, $currentFieldSet);

                    break;
                case FragmentSpread::class:
                    // fallthrough
                case InlineFragment::class:
                    $this->validateDepth($fieldDepth, $selection->children);

                    break;
            }
        }
    }
}
