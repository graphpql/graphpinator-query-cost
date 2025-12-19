<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Tests;

use Graphpinator\Graphpinator;
use Graphpinator\Module\ModuleSet;
use Graphpinator\QueryCost\Exception\MaximalDepthWasReached;
use Graphpinator\QueryCost\MaxDepthModule;
use Graphpinator\Request\JsonRequestFactory;
use Graphpinator\SimpleContainer;
use Graphpinator\Typesystem\Container;
use Graphpinator\Typesystem\Field\ResolvableField;
use Graphpinator\Typesystem\Field\ResolvableFieldSet;
use Graphpinator\Typesystem\Schema;
use Graphpinator\Typesystem\Type;
use Infinityloop\Utils\Json;
use PHPUnit\Framework\TestCase;

final class MaxDepthModuleTest extends TestCase
{
    public static ?Type $query = null;

    public static function getQuery($type) : Type
    {
        return self::$query ??= new class ($type) extends Type {
            public function __construct(
                private Type $type,
            )
            {
                parent::__construct();
            }

            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'field',
                        $this->type->notNull(),
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
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'field',
                        $this,
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                    ResolvableField::create(
                        'scalar',
                        Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                    ResolvableField::create(
                        'simpleField',
                        MaxDepthModuleTest::getSimpleType(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
    }

    public static function getSimpleType() : Type
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
                        'field',
                        Container::String()->notNull(),
                        static function ($parent) : string {
                            return 'testValue';
                        },
                    ),
                    ResolvableField::create(
                        'scalar',
                        Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
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
                    'query' => '{ field { field { field { scalar simpleField { scalar field } } } } }',
                ]),
                Json::fromNative(
                    (object) [
                        'data' => [
                            'field' => [
                                'field' => [
                                    'field' => [
                                        'scalar' => 1,
                                        'simpleField' => ['scalar' => 1, 'field' => 'testValue'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ),
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     */
    public function testSimple(Json $request, Json $expected) : void
    {
        $graphpinator = new Graphpinator(
            self::getSchema(),
            false,
            new ModuleSet([new MaxDepthModule(5)]),
        );
        $result = $graphpinator->run(new JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function testInvalid() : void
    {
        $exception = new MaximalDepthWasReached(5);

        self::assertSame('Maximal fields depth 5 was reached.', $exception->getMessage());
        self::assertTrue($exception->isOutputable());

        $this->expectException(MaximalDepthWasReached::class);

        $graphpinator = new Graphpinator(
            self::getSchema(),
            false,
            new ModuleSet([new MaxDepthModule(2)]),
        );
        $graphpinator->run(new JsonRequestFactory(Json::fromNative((object) [
             'query' => '{ field { field { field { scalar } } } }',
        ])));
    }

    private static function getContainer() : SimpleContainer
    {
        return new SimpleContainer([self::getQuery(self::getTestType())], []);
    }

    private static function getSchema() : Schema
    {
        return new Schema(self::getContainer(), self::getQuery(self::getTestType()));
    }
}
