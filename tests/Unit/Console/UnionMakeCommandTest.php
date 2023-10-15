<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\UnionMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class UnionMakeCommandTest extends TestCase
{
    use MakeCommandAssertionTrait;

    /**
     * @dataProvider dataForMakeCommand
     */
    public function testCommand(
        string $inputName,
        string $expectedFilename,
        string $expectedClassDefinition,
        string $expectedGraphqlName
    ): void {
        $this->assertMakeCommand(
            'Union',
            UnionMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Unions',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Unions/Example.php',
                'expectedClassDefinition' => 'Example extends UnionType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleType' => [
                'inputName' => 'ExampleType',
                'expectedFilename' => 'GraphQL/Unions/ExampleType.php',
                'expectedClassDefinition' => 'ExampleType extends UnionType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleUnion' => [
                'inputName' => 'ExampleUnion',
                'expectedFilename' => 'GraphQL/Unions/ExampleUnion.php',
                'expectedClassDefinition' => 'ExampleUnion extends UnionType',
                'expectedGraphqlName' => "'name' => 'ExampleUnion',",
            ],
            'ExampleUnionType' => [
                'inputName' => 'ExampleUnionType',
                'expectedFilename' => 'GraphQL/Unions/ExampleUnionType.php',
                'expectedClassDefinition' => 'ExampleUnionType extends UnionType',
                'expectedGraphqlName' => "'name' => 'ExampleUnion',",
            ],
        ];
    }
}
