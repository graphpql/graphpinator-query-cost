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
use Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached;
use Graphpinator\Request\Request;
use Graphpinator\Resolver\Result;

final class MaxNodesModule implements Module
{
    public function __construct(
        private int $maxQueryCost,
        /** @var list<string> */
        private array $limitArgumentNames = ['limit', 'first', 'last'],
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
        return $request;
    }

    #[\Override]
    public function processFinalized(FinalizedRequest $request) : FinalizedRequest
    {
        $queryCost = $this->countSelectionSetCost($request->operation->children);

        if ($queryCost > $this->maxQueryCost) {
            throw new MaximalQueryCostWasReached($queryCost);
        }

        return $request;
    }

    #[\Override]
    public function processResult(Result $result) : Result
    {
        return $result;
    }

    private function countFieldCost(Field $field) : int
    {
        $currentFields = $field->children;

        if ($currentFields === null) {
            return 1;
        }

        $fieldSetCost = $this->countSelectionSetCost($currentFields);
        $currentArguments = $field->arguments;
        $multiplier = 1;

        foreach ($currentArguments as $argument) {
            if (\in_array($argument->argument->getName(), $this->limitArgumentNames, true)) {
                $argumentRawValue = $argument->value->getRawValue();

                if (\is_int($argumentRawValue) && $argumentRawValue > 0) {
                    $multiplier = $argumentRawValue;

                    break;
                }
            }
        }

        return ($fieldSetCost + 1) * $multiplier;
    }

    private function countFragmentSpreadCost(FragmentSpread $fragmentSpread) : int
    {
        return $this->countSelectionSetCost($fragmentSpread->children);
    }

    private function countInlineFragmentCost(InlineFragment $inlineFragment) : int
    {
        return $this->countSelectionSetCost($inlineFragment->children);
    }

    private function countSelectionSetCost(SelectionSet $selectionSet) : int
    {
        $cost = 0;

        foreach ($selectionSet as $selection) {
            $cost += match ($selection::class) {
                Field::class => $this->countFieldCost($selection),
                FragmentSpread::class => $this->countFragmentSpreadCost($selection),
                InlineFragment::class => $this->countInlineFragmentCost($selection),
                default => throw new \LogicException('Unknown selection type'),
            };
        }

        return $cost;
    }
}
