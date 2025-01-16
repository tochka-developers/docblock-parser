<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\ReturnTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ReturnTag::class)]
class ReturnTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ReturnTag(new StringType()), '@return string'];
        yield [new ReturnTag(new StringType(), 'Hello'), '@return string Hello'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
