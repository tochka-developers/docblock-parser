<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\ParamTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ParamTag::class)]
class ParamTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ParamTag('name', new StringType()), '@param string $name'];
        yield [new ParamTag('name', new StringType(), isReference: true), '@param string &$name'];
        yield [new ParamTag('name', new StringType(), isVariadic: true), '@param string ...$name'];
        yield [new ParamTag('name', new StringType(), description: 'Hello'), '@param string $name Hello'];
        yield [
            new ParamTag(
                'name',
                new StringType(),
                true,
                true,
                'Hello',
            ),
            '@param string ...&$name Hello',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
