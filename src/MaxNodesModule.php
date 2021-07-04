<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

final class MaxNodesModule implements \Graphpinator\Module\Module
{
    use \Nette\SmartObject;

    public function __construct(
        private int $maxQueryCost,
        private array $limitArgumentNames = ['limit', 'first', 'last'],
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
        return $request;
    }

    public function processFinalized(\Graphpinator\Normalizer\FinalizedRequest $request) : \Graphpinator\Normalizer\FinalizedRequest
    {
        $queryCost = $this->countFieldSetCost($request->getOperation()->getFields());

        if ($queryCost > $this->maxQueryCost) {
            throw new \Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached($queryCost);
        }

        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function countFieldCost(\Graphpinator\Normalizer\Field\Field $field) : int
    {
        $currentFields = $field->getFields();

        if ($currentFields === null) {
            return 1;
        }

        $fieldSetCost = $this->countFieldSetCost($currentFields);
        $currentArguments = $field->getArguments();
        $multiplier = 1;

        foreach ($currentArguments as $argument) {
            if (\in_array($argument->getArgument()->getName(), $this->limitArgumentNames, true)) {
                $argumentRawValue = $argument->getValue()->getRawValue();

                if (\is_int($argumentRawValue) && $argumentRawValue > 0) {
                    $multiplier = $argumentRawValue;
                    
                    break;
                }
            }
        }

        return ($fieldSetCost + 1) * $multiplier;
    }

    private function countFieldSetCost(\Graphpinator\Normalizer\Field\FieldSet $fieldSet) : int
    {
        $fieldCost = 0;

        foreach ($fieldSet as $field) {
            $fieldCost += $this->countFieldCost($field);
        }

        return $fieldCost;
    }
}
