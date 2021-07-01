# GraPHPinator Query cost [![PHP](https://github.com/infinityloop-dev/graphpinator-query-cost/workflows/PHP/badge.svg?branch=master)](https://github.com/infinityloop-dev/graphpinator-query-cost/actions?query=workflow%3APHP) [![codecov](https://codecov.io/gh/infinityloop-dev/graphpinator-query-cost/branch/master/graph/badge.svg)](https://codecov.io/gh/infinityloop-dev/graphpinator-query-cost)

:zap::globe_with_meridians::zap: Modules to limit query cost by restricting maximum depth or number of nodes.

## Introduction



## Installation

Install package using composer

```composer require infinityloop-dev/graphpinator-query-cost```

## How to use

This package includes two modules. They can be used together or each on their own.
- `MaxDepthModule` validates maximum depth of a query.
- `MaxNodesModule` validates that size of a query does not exceed maximum number of nodes. 
    - One node is essentially a single value which is to be resolved. 
    - This module automatically recognises "multiplier" arguments, such as `limit`, which multiply inner number of nodes for that field.
        - Default multiplier arguments are `['limit', 'first', 'last']`, but can be changed using second constructor argument.
        - If you wish to disable this feature, set the constructor argument to empty array.

1. Register selected modules to GraPHPinator:

```php
$depthModule = new \Graphpinator\QueryCost\MaxDepthModule(
    10, // selected maximum depth
);
$nodesModule = new \Graphpinator\QueryCost\MaxNodesModule(
    10000, // selected number of nodes
    ['limit'], // optional: multiplier argument names
);
$graphpinator = new \Graphpinator\Graphpinator(
    $schema,
    $catchExceptions,
    new \Graphpinator\Module\ModuleSet([$depthModule, $nodesModule /* possibly other modules */]),
    $logger,
);
```

2. You are all set, queries are validated for maximum depth/maximum number of nodes.
