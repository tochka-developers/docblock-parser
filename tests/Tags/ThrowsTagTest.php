<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\ThrowsTag;
use Tochka\Types\Atomic\ClassType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(ThrowsTag::class)]
class ThrowsTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new ThrowsTag(new ClassType('HelloClass')), '@throws HelloClass'];
        yield [new ThrowsTag(new ClassType('HelloClass'), 'Description'), '@throws HelloClass Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
