<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\SourceTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(SourceTag::class)]
class SourceTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new SourceTag(), '@source'];
        yield [new SourceTag('Description'), '@source Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
