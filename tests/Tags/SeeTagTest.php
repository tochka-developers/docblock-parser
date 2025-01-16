<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\SeeTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(SeeTag::class)]
class SeeTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new SeeTag(), '@see'];
        yield [new SeeTag('Description'), '@see Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
