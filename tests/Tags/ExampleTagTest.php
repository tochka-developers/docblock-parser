<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\ExampleTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ExampleTag::class)]
class ExampleTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ExampleTag(), '@example'];
        yield [new ExampleTag('Description'), '@example Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
