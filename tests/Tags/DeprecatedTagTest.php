<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\DeprecatedTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(DeprecatedTag::class)]
class DeprecatedTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new DeprecatedTag(), '@deprecated'];
        yield [new DeprecatedTag('Description'), '@deprecated Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
