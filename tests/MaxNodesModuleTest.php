<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Tests;

final class MaxNodesModuleTest extends \PHPUnit\Framework\TestCase
{
    public function testSimple() : void
    {
        $type = new class extends \Graphpinator\Typesystem\Type {
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldA',
                        $this,
                        static function ($parent, $limit) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'limit',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldB',
                        $this,
                        static function ($parent, $first) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'first',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldC',
                        $this,
                        static function ($parent, $last) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'last',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'scalar',
                        \Graphpinator\Container\Container::Int()->notNull(),
                        static function ($parent, $arg) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'arg',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldNoArgs',
                        \Graphpinator\Container\Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
        $query = new class ($type) extends \Graphpinator\Typesystem\Type {
            public function __construct(
                private \Graphpinator\Typesystem\Type $type,
            )
            {
                parent::__construct();
            }

            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'field',
                        $this->type->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
        $container = new \Graphpinator\SimpleContainer([$query], []);
        $schema = new \Graphpinator\Typesystem\Schema($container, $query);

        $graphpinator = new \Graphpinator\Graphpinator(
            $schema,
            false,
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxNodesModule(162)]),
        );
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory(\Infinityloop\Utils\Json::fromNative((object) [
            'query' => '{ field { fieldA { fieldA(limit: 10) { fieldB(first: 5) { fieldC(last: 2) { scalar(arg: 5) } } } } } }',
        ])));

        self::assertSame(
            \Infinityloop\Utils\Json::fromNative(
                (object) ['data' => ['field' => ['fieldA' => ['fieldA' => ['fieldB' => ['fieldC' => ['scalar' => 1]]]]]]],
            )->toString(),
            $result->toString(),
        );
    }

    public function testInvalid() : void
    {
        $this->expectException(\Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached::class);

        $type = new class extends \Graphpinator\Typesystem\Type {
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldA',
                        $this,
                        static function ($parent, $limit) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'limit',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldB',
                        $this,
                        static function ($parent, $first) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'first',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'fieldC',
                        $this,
                        static function ($parent, $last) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'last',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'scalar',
                        \Graphpinator\Container\Container::Int()->notNull(),
                        static function ($parent, $arg) : int {
                            return 1;
                        },
                    )->setArguments(new \Graphpinator\Typesystem\Argument\ArgumentSet([
                        \Graphpinator\Argument\Argument::create(
                            'arg',
                            \Graphpinator\Container\Container::Int(),
                        ),
                    ])),
                ]);
            }
        };
        $query = new class ($type) extends \Graphpinator\Typesystem\Type {
            public function __construct(
                private \Graphpinator\Typesystem\Type $type,
            )
            {
                parent::__construct();
            }

            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'field',
                        $this->type->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
        $container = new \Graphpinator\SimpleContainer([$query], []);
        $schema = new \Graphpinator\Typesystem\Schema($container, $query);

        $graphpinator = new \Graphpinator\Graphpinator(
            $schema,
            false,
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxNodesModule(10)]),
        );
        $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory(\Infinityloop\Utils\Json::fromNative((object) [
            'query' => '{ field { fieldA { fieldA(limit: 10) { fieldB(first: 10) { fieldC { scalar } } } } } }',
        ])));
    }

    public function testException() : void
    {
        $exception = new \Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached(10);

        self::assertTrue($exception->isOutputable());
        self::assertSame('Maximal query cost 10 was reached.', $exception->getMessage());
    }
}
