<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

final class MaxNodesModule implements \Graphpinator\Module\Module
{
    use \Nette\SmartObject;

    private const ARGUMENT_NAMES = [
        'limit',
        'first',
        'last',
    ];
    private int $actualQueryCost = 0;

    public function __construct(
        private int $maxQueryCost,
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
        $queryCost = $this->countCost(1, $request->getOperation()->getFields());

        if ($queryCost > $this->maxQueryCost) {
            throw new \Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached($queryCost);
        }

        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function countCost(int $queryCost, \Graphpinator\Normalizer\Field\FieldSet $fieldSet) : int
    {
        foreach ($fieldSet as $field) {
            $currentFieldSet = $field->getFields();

            if ($currentFieldSet === null) {
                return 0;
            }

            ++$queryCost;
            $subCost = $this->countCost($queryCost, $currentFieldSet);
            $currentArguments = $field->getArguments();

            if ($currentArguments->count() >= 1) {
                foreach ($currentArguments as $argument) {
                    $currentArgumentName = $argument->getArgument()->getName();

                    if (\in_array($currentArgumentName, self::ARGUMENT_NAMES)) {
                        $argumentRawValue = $argument->getValue()->getRawValue();

                        if (\is_int($argumentRawValue) && $argumentRawValue > 0) {
                            return $subCost === 0
                                ? $argumentRawValue * $queryCost
                                : $argumentRawValue * $subCost;
                        }
                    }
                }
            }
        }

        return $subCost;
    }
}
