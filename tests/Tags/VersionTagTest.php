<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\VersionTag;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(VersionTag::class)]
class VersionTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new VersionTag(), '@version'];
        yield [new VersionTag('Description'), '@version Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
