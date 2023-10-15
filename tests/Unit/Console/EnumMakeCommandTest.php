<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\Console;

use Rebing\GraphQL\Console\EnumMakeCommand;
use Rebing\GraphQL\Tests\Support\Traits\MakeCommandAssertionTrait;
use Rebing\GraphQL\Tests\TestCase;

class EnumMakeCommandTest extends TestCase
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
            'Enum',
            EnumMakeCommand::class,
            $inputName,
            $expectedFilename,
            'App\\\\GraphQL\\\\Enums',
            $expectedClassDefinition,
            $expectedGraphqlName
        );
    }

    public static function dataForMakeCommand(): array
    {
        return [
            'Example' => [
                'inputName' => 'Example',
                'expectedFilename' => 'GraphQL/Enums/Example.php',
                'expectedClassDefinition' => 'Example extends EnumType',
                'expectedGraphqlName' => "'name' => 'Example',",
            ],
            'ExampleEnum' => [
                'inputName' => 'ExampleEnum',
                'expectedFilename' => 'GraphQL/Enums/ExampleEnum.php',
                'expectedClassDefinition' => 'ExampleEnum extends EnumType',
                'expectedGraphqlName' => "'name' => 'ExampleEnum',",
            ],
            'ExampleEnumType' => [
                'inputName' => 'ExampleEnumType',
                'expectedFilename' => 'GraphQL/Enums/ExampleEnumType.php',
                'expectedClassDefinition' => 'ExampleEnumType extends EnumType',
                'expectedGraphqlName' => "'name' => 'ExampleEnum',",
            ],
        ];
    }
}
