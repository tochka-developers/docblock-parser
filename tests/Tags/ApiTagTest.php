<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\ApiTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ApiTag::class)]
class ApiTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ApiTag(), '@api'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
