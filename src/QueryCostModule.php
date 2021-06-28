<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost;

final class QueryCostModule implements \Graphpinator\Module\Module
{
    use \Nette\SmartObject;

    private const ARGUMENT_NAMES = [
        'limit',
        'first',
        'last',
    ];
    private array $argumentValues = [];

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
        $this->countCost(1, $request->getOperation()->getFields());

        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function countCost(int $queryCost, \Graphpinator\Normalizer\Field\FieldSet $fieldSet) : void
    {
        foreach ($fieldSet as $field) {
            foreach ($field->getArguments() as $argument) {
                $currentArgumentName = $argument->getArgument()->getName();

                if (!\in_array($currentArgumentName, self::ARGUMENT_NAMES)) {
                    continue;
                }

                $this->argumentValues[] = $argument->getValue()->getRawValue();
            }

            $currentFieldSet = $field->getFields();

            if ($currentFieldSet === null) {
                while (\count($this->argumentValues) > 0) {
                    $argumentValue = \array_pop($this->argumentValues);

                    if ($argumentValue !== null) {
                        $queryCost *= $argumentValue;
                    }

                    if ($queryCost > $this->maxQueryCost) {
                        throw new \Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached($this->maxQueryCost);
                    }
                }

                continue;
            }

            ++$queryCost;
            $this->countCost($queryCost, $currentFieldSet);
        }
    }
}
