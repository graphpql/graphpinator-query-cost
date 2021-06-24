<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

use \Graphpinator\QueryCost\Exception\MaximalDepthWasReached;

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
            $this->countDepth(1, $operation->getFields());
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

    private function countDepth(int $fieldDepth, \Graphpinator\Normalizer\Field\FieldSet $fieldSet) : void
    {
        foreach ($fieldSet as $field) {
            $currentFieldSet = $field->getFields();

            if ($currentFieldSet === null) {
                continue;
            }

            if ($fieldDepth > $this->maxDepth) {
                throw new MaximalDepthWasReached();
            }

            ++$fieldDepth;
            $this->countDepth($fieldDepth, $currentFieldSet);
        }
    }
}
