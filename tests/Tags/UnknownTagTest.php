<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\SelfOutTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\UnknownTag;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(UnknownTag::class)]
class UnknownTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [
            new UnknownTag(
                'custom',
                new SelfOutTagValueNode(
                    new ConstTypeNode(
                        new ConstExprStringNode('string'),
                    ),
                    'Hello',
                ),
            ),
            '@custom string Hello',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
