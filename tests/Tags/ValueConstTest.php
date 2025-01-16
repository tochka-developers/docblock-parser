<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayItemNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\ValueConst;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ValueConst::class)]
class ValueConstTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ValueConst(new ConstExprStringNode('Hello')), "'Hello'"];
        yield [new ValueConst(new ConstExprIntegerNode('123')), '123'];
        yield [new ValueConst(new ConstExprArrayNode([])), '[]'];
        yield [
            new ValueConst(
                new ConstExprArrayNode([
                    new ConstExprArrayItemNode(null, new ConstExprStringNode('Foo')),
                    new ConstExprArrayItemNode(null, new ConstExprStringNode('Bar')),
                ]),
            ),
            "['Foo', 'Bar']",
        ];
        yield [
            new ValueConst(
                new ConstExprArrayNode([
                    new ConstExprArrayItemNode(new ConstExprStringNode('foo'), new ConstExprStringNode('Foo')),
                    new ConstExprArrayItemNode(new ConstExprStringNode('bar'), new ConstExprStringNode('Bar')),
                ]),
            ),
            "['foo' => 'Foo', 'bar' => 'Bar']",
        ];
        yield [new ValueConst(new ConstExprNullNode()), 'null'];
        yield [new ValueConst(new ConstExprTrueNode()), 'true'];
        yield [new ValueConst(new ConstExprFalseNode()), 'false'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(ValueConst $const, string $expected): void
    {
        self::assertEquals($expected, (string) $const);
    }
}
