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
        $queryCost = $this->countSelectionSetCost($request->getOperation()->getSelections());

        if ($queryCost > $this->maxQueryCost) {
            throw new \Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached($queryCost);
        }

        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function countFieldCost(\Graphpinator\Normalizer\Selection\Field $field) : int
    {
        $currentFields = $field->getSelections();

        if ($currentFields === null) {
            return 1;
        }

        $fieldSetCost = $this->countSelectionSetCost($currentFields);
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

    private function countFragmentSpreadCost(\Graphpinator\Normalizer\Selection\FragmentSpread $fragmentSpread) : int
    {
        return $this->countSelectionSetCost($fragmentSpread->getSelections());
    }

    private function countInlineFragmentCost(\Graphpinator\Normalizer\Selection\InlineFragment $inlineFragment) : int
    {
        return $this->countSelectionSetCost($inlineFragment->getSelections());
    }

    private function countSelectionSetCost(\Graphpinator\Normalizer\Selection\SelectionSet $selectionSet) : int
    {
        $cost = 0;

        foreach ($selectionSet as $selection) {
            $cost += match ($selection::class) {
                \Graphpinator\Normalizer\Selection\Field::class => $this->countFieldCost($selection),
                \Graphpinator\Normalizer\Selection\FragmentSpread::class => $this->countFragmentSpreadCost($selection),
                \Graphpinator\Normalizer\Selection\InlineFragment::class => $this->countInlineFragmentCost($selection),
            };
        }

        return $cost;
    }
}
