<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

final class QueryCostModule implements \Graphpinator\Module\Module
{
    use \Nette\SmartObject;

    public function __construct(private int $depth)
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
        $fieldDepth = 0;

        foreach ($request->getOperations()->toArray() as $operation) {
            foreach ($operation->getFields() as $field) {
                $currentFieldSet = $field->getFields();

                if ($currentFieldSet === null) {
                    continue;
                }

                if ($this->validateDepth($fieldDepth)) {
                    ++$fieldDepth;
                }

                $fieldDepth = $this->countDepth($fieldDepth, $currentFieldSet);
            }
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

    private function countDepth(int $fieldDepth, \Graphpinator\Normalizer\Field\FieldSet $fieldSet) : int
    {
        foreach ($fieldSet as $field) {
            $currentFieldSet = $field->getFields();

            if ($currentFieldSet === null) {
                continue;
            }

            if ($this->validateDepth($fieldDepth)) {
                ++$fieldDepth;
            }

            $this->countDepth($fieldDepth, $currentFieldSet);
        }

        return $fieldDepth;
    }

    private function validateDepth(int $fieldDepth) : bool
    {
        return $this->depth >= $fieldDepth
            ? true
            : throw new \Graphpinator\Module\QueryCost\Exception\MaximalDepthWasReached();
    }
}
