<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\SinceTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(SinceTag::class)]
class SinceTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new SinceTag(), '@since'];
        yield [new SinceTag('Description'), '@since Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
