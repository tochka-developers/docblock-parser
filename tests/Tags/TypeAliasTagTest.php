<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\TypeAliasTag;
use Tochka\Types\Alias\IntRangeType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(TypeAliasTag::class)]
class TypeAliasTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new TypeAliasTag('MyAlias', new IntRangeType(1, 20)), '@psalm-type MyAlias = int<1, 20>'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
