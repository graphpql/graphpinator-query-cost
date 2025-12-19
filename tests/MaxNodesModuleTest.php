<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Tests;

use Graphpinator\Graphpinator;
use Graphpinator\Module\ModuleSet;
use Graphpinator\QueryCost\Exception\MaximalQueryCostWasReached;
use Graphpinator\QueryCost\MaxNodesModule;
use Graphpinator\Request\JsonRequestFactory;
use Graphpinator\SimpleContainer;
use Graphpinator\Typesystem\Argument\Argument;
use Graphpinator\Typesystem\Argument\ArgumentSet;
use Graphpinator\Typesystem\Container;
use Graphpinator\Typesystem\Field\ResolvableField;
use Graphpinator\Typesystem\Field\ResolvableFieldSet;
use Graphpinator\Typesystem\Schema;
use Graphpinator\Typesystem\Type;
use Infinityloop\Utils\Json;
use PHPUnit\Framework\TestCase;

final class MaxNodesModuleTest extends TestCase
{
    public static ?Type $query = null;

    public static function getQuery() : Type
    {
        return self::$query ??= new class () extends Type {
            protected const NAME = 'Query';

            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'field',
                        MaxNodesModuleTest::getTestType()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
    }

    public static function getTestType() : Type
    {
        return new class extends Type {
            protected const NAME = 'Type';

            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'fieldA',
                        $this,
                        static function ($parent, $limit) : int {
                            return 1;
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'limit',
                            Container::Int(),
                        ),
                    ])),
                    ResolvableField::create(
                        'stringField',
                        $this,
                        static function ($parent, $limit) : string {
                            return 'stringValue';
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'limit',
                            Container::String(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldB',
                        $this,
                        static function ($parent, $first) : int {
                            return 1;
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'first',
                            Container::Int(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldC',
                        $this,
                        static function ($parent, $last) : int {
                            return $last;
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'last',
                            Container::Int(),
                        ),
                    ])),
                    ResolvableField::create(
                        'scalar',
                        Container::Int()->notNull(),
                        static function ($parent, $arg) : int {
                            return 1;
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'arg',
                            Container::Int(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldNoArgs',
                        Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                    ResolvableField::create(
                        'emptyField',
                        MaxNodesModuleTest::getOneFieldType(),
                        static function ($parent) : void {
                        },
                    ),
                    ResolvableField::create(
                        'multipleArgumentField',
                        $this,
                        static function ($parent, $limit, $last, $first) : void {
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'limit',
                            Container::Int(),
                        ),
                        Argument::create(
                            'last',
                            Container::Int(),
                        ),
                        Argument::create(
                            'first',
                            Container::Int(),
                        ),
                    ])),
                    ResolvableField::create(
                        'lastField',
                        $this,
                        static function ($parent, $limit) : void {
                        },
                    )->setArguments(new ArgumentSet([
                        Argument::create(
                            'limit',
                            Container::Int(),
                        )->setDefaultValue(0),
                    ])),
                ]);
            }
        };
    }

    public static function getOneFieldType() : Type
    {
        return new class extends Type {
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'name',
                        Container::String(),
                        static function () : string {
                            return 'testName';
                        },
                    ),
                ]);
            }
        };
    }

    public static function simpleDataProvider() : array
    {
        return [
            [
                Json::fromNative((object) [
                    'query' => '{ field { fieldA { fieldA(limit: 10) { fieldB(first: 5) { fieldC(last: 2) { scalar(arg: 5) } } } } } }',
                    ]),
                Json::fromNative(
                    (object) ['data' => ['field' => ['fieldA' => ['fieldA' => ['fieldB' => ['fieldC' => ['scalar' => 1]]]]]]],
                ),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ field { stringField(limit: "testVal") {fieldA(limit: 0) { scalar } } } }',
                ]),
                Json::fromNative(
                    (object) ['data' => ['field' => ['stringField' => ['fieldA' => ['scalar' => 1]]]]],
                ),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ field { fieldA(limit: -1) { stringField(limit: "test") { scalar emptyField { name } } } } }',
                ]),
                Json::fromNative(
                    (object) ['data' => ['field' => ['fieldA' => ['stringField' => ['scalar' => 1, 'emptyField' => null]]]]],
                ),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ field { lastField(limit: 0) { scalar } } }',
                ]),
                Json::fromNative(
                    (object) ['data' => ['field' => ['lastField' => null]]],
                ),
            ],
            [
                Json::fromNative((object) [
                    'query' => '{ field { multipleArgumentField(limit: 0, last: 5, first: 20) { scalar } } }',
                ]),
                Json::fromNative(
                    (object) ['data' => ['field' => ['multipleArgumentField' => null]]],
                ),
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     * @param Json $request
     * @param Json $expected
     */
    public function testSimple(Json $request, Json $expected) : void
    {
        $graphpinator = new Graphpinator(
            self::getSchema(),
            false,
            new ModuleSet([new MaxNodesModule(262, ['limit', 'last', 'first'])]),
        );
        $result = $graphpinator->run(new JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function testInvalid() : void
    {
        $exception = new MaximalQueryCostWasReached(10);

        self::assertSame('Maximal query cost 10 was reached.', $exception->getMessage());
        self::assertTrue($exception->isOutputable());

        $this->expectException(MaximalQueryCostWasReached::class);

        $graphpinator = new Graphpinator(
            self::getSchema(),
            false,
            new ModuleSet([new MaxNodesModule(311)]),
        );
        $graphpinator->run(new JsonRequestFactory(Json::fromNative((object) [
            'query' => '{ field { fieldA { fieldA(limit: 10) { fieldB(first: 10) { fieldC { scalar } } } } } }',
        ])));
    }

    private static function getContainer() : SimpleContainer
    {
        return new SimpleContainer([self::getQuery(), self::getTestType()], []);
    }

    private static function getSchema() : Schema
    {
        return new Schema(self::getContainer(), self::getQuery());
    }
}
