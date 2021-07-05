<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Tests;

use \Infinityloop\Utils\Json;

final class MaxDepthModuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return \Graphpinator\Typesystem\Type|\Graphpinator\QueryCost\Tests\__anonymous @260
     */
    public static function getQuery($type) : \Graphpinator\Typesystem\Type|__anonymous
    {
        return new class ($type) extends \Graphpinator\Typesystem\Type {
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
    }

    /**
     * @return \Graphpinator\Typesystem\Type|\Graphpinator\QueryCost\Tests\__anonymous @1247
     */
    public static function getTestType() : \Graphpinator\Typesystem\Type|__anonymous
    {
        return new class extends \Graphpinator\Typesystem\Type {
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'field',
                        $this,
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'scalar',
                        \Graphpinator\Container\Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
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

    /**
     * @return \Graphpinator\Typesystem\Type|\Graphpinator\QueryCost\Tests\__anonymous @2760
     */
    public static function getSimpleType() : \Graphpinator\Typesystem\Type|__anonymous
    {
        return new class extends \Graphpinator\Typesystem\Type {
            public function validateNonNullValue(mixed $rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Typesystem\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Typesystem\Field\ResolvableFieldSet([
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'field',
                        \Graphpinator\Container\Container::String()->notNull(),
                        static function ($parent) : string {
                            return 'testValue';
                        },
                    ),
                    \Graphpinator\Typesystem\Field\ResolvableField::create(
                        'scalar',
                        \Graphpinator\Container\Container::Int()->notNull(),
                        static function ($parent) : int {
                            return 1;
                        },
                    ),
                ]);
            }
        };
    }

    public function simpleDataProvider() : array
    {
        return [
            [
                \Infinityloop\Utils\Json::fromNative((object) [
                    'query' => '{ field { field { field { scalar simpleField { scalar field } } } } }',
                ]),
                \Infinityloop\Utils\Json::fromNative(
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
     * @param \Infinityloop\Utils\Json $request
     * @param \Infinityloop\Utils\Json $expected
     */
    public function testSimple(Json $request, Json $expected) : void
    {
        $graphpinator = new \Graphpinator\Graphpinator(
            $this->getSchema(),
            false,
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxDepthModule(4)]),
        );
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    public function testInvalid() : void
    {
        $exception = new \Graphpinator\QueryCost\Exception\MaximalDepthWasReached(5);

        self::assertSame('Maximal fields depth 5 was reached.', $exception->getMessage());
        self::assertTrue($exception->isOutputable());

        $this->expectException(\Graphpinator\QueryCost\Exception\MaximalDepthWasReached::class);

        $graphpinator = new \Graphpinator\Graphpinator(
            $this->getSchema(),
            false,
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxDepthModule(2)]),
        );
        $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory(\Infinityloop\Utils\Json::fromNative((object) [
             'query' => '{ field { field { field { scalar } } } }',
        ])));
    }

    private function getContainer() : \Graphpinator\SimpleContainer
    {
        return new \Graphpinator\SimpleContainer([self::getQuery(self::getTestType())], []);
    }

    private function getSchema() : \Graphpinator\Typesystem\Schema
    {
        return new \Graphpinator\Typesystem\Schema(self::getContainer(), self::getQuery(self::getTestType()));
    }
}
