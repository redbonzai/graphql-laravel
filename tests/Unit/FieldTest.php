<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\MockObject\MockObject;
use Rebing\GraphQL\Tests\Support\Objects\ExampleField;
use Rebing\GraphQL\Tests\TestCase;

class FieldTest extends TestCase
{
    /**
     * @return class-string<ExampleField>
     */
    protected function getFieldClass()
    {
        return ExampleField::class;
    }

    protected function resolveInfoMock(): MockObject
    {
        return $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetAttributes(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $attributes = $field->getAttributes();

        self::assertArrayHasKey('name', $attributes);
        self::assertArrayHasKey('type', $attributes);
        self::assertArrayHasKey('args', $attributes);
        self::assertArrayHasKey('resolve', $attributes);
        self::assertInstanceOf(Closure::class, $attributes['resolve']);
        self::assertInstanceOf(\get_class($field->type()), $attributes['type']);
    }

    public function testResolve(): void
    {
        $class = $this->getFieldClass();
        $field = $this->getMockBuilder($class)
                    ->onlyMethods(['resolve'])
                    ->getMock();

        $field->expects(self::once())
            ->method('resolve');

        $attributes = $field->getAttributes();
        $attributes['resolve'](null, [], [], $this->resolveInfoMock());
    }

    public function testToArray(): void
    {
        $class = $this->getFieldClass();
        $field = new $class();
        $array = $field->toArray();

        $attributes = $field->getAttributes();
        self::assertEquals($attributes, $array);
    }
}
