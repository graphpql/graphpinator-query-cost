<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

final class MaxDepthModule implements \Graphpinator\Module\Module
{
    use \Nette\SmartObject;

    public function __construct(
        private int $maxDepth,
    )
    {
    }

    public function processRequest(\Graphpinator\Request\Request $request) : \Graphpinator\Request\Request
    {
        return $request;
    }

    public function processParsed(\Graphpinator\Parser\ParsedRequest $request) : \Graphpinator\Parser\ParsedRequest
    {
        return $request;
    }

    public function processNormalized(\Graphpinator\Normalizer\NormalizedRequest $request) : \Graphpinator\Normalizer\NormalizedRequest
    {
        foreach ($request->getOperations() as $operation) {
            $this->validateDepth(1, $operation->getSelections());
        }

        return $request;
    }

    public function processFinalized(\Graphpinator\Normalizer\FinalizedRequest $request) : \Graphpinator\Normalizer\FinalizedRequest
    {
        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function validateDepth(int $fieldDepth, \Graphpinator\Normalizer\Selection\SelectionSet $selectionSet) : void
    {
        if ($fieldDepth > $this->maxDepth) {
            throw new \Graphpinator\QueryCost\Exception\MaximalDepthWasReached($this->maxDepth);
        }

        foreach ($selectionSet as $selection) {
            switch ($selection::class) {
                case \Graphpinator\Normalizer\Selection\Field::class:
                    $currentFieldSet = $selection->getSelections();

                    if ($currentFieldSet === null) {
                        continue 2;
                    }

                    $this->validateDepth(++$fieldDepth, $currentFieldSet);

                    break;
                case \Graphpinator\Normalizer\Selection\FragmentSpread::class:
                    // fallthrough
                case \Graphpinator\Normalizer\Selection\InlineFragment::class:
                    $this->validateDepth($fieldDepth, $selection->getSelections());

                    break;
            }
        }
    }
}
