<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\AuthorTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(AuthorTag::class)]
class AuthorTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new AuthorTag(), '@author'];
        yield [new AuthorTag('Description'), '@author Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }

    public function testConstruct(): void
    {
        $tag = new AuthorTag('hello');
        self::assertEquals('hello', $tag->description);
    }
}
