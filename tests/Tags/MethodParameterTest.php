<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\MethodParameter;
use Tochka\DocBlockParser\Tags\ValueConst;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(MethodParameter::class)]
class MethodParameterTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new MethodParameter('name'), '$name'];
        yield [new MethodParameter('name', new StringType()), 'string $name'];
        yield [new MethodParameter('name', isReference: true), '&$name'];
        yield [new MethodParameter('name', isVariadic: true), '...$name'];
        yield [
            new MethodParameter('name', defaultValue: new ValueConst(new ConstExprStringNode('hello'))),
            '$name = \'hello\'',
        ];
        yield [new MethodParameter('name', description: 'Hello'), '$name'];
        yield [
            new MethodParameter(
                'name',
                new StringType(),
                true,
                true,
                new ValueConst(new ConstExprIntegerNode('123')),
            ),
            'string ...&$name = 123',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(MethodParameter $parameter, string $expected): void
    {
        self::assertEquals($expected, (string) $parameter);
    }
}
