<?php

declare(strict_types = 1);

namespace Graphpinator\QueryCost\Tests;

final class QueryCostModuleTest extends \PHPUnit\Framework\TestCase
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
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxDepthModule(5)]),
        );
        $result = $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory(\Infinityloop\Utils\Json::fromNative((object) [
            'query' => '{ field { field { field { scalar } } } }',
        ])));

        self::assertSame(
            \Infinityloop\Utils\Json::fromNative((object) ['data' => ['field' => ['field' => ['field' => ['scalar' => 1]]]]])->toString(),
            $result->toString(),
        );
    }

    public function testInvalid() : void
    {
        $this->expectException(\Graphpinator\QueryCost\Exception\MaximalDepthWasReached::class);

        $type = new class extends \Graphpinator\Typesystem\Type {
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
            new \Graphpinator\Module\ModuleSet([new \Graphpinator\QueryCost\MaxDepthModule(2)]),
        );
        $graphpinator->run(new \Graphpinator\Request\JsonRequestFactory(\Infinityloop\Utils\Json::fromNative((object) [
             'query' => '{ field { field { field { scalar } } } }',
        ])));
    }
}
