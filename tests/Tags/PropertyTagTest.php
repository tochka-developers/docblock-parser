<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\PropertyTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(PropertyTag::class)]
class PropertyTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new PropertyTag('name', new StringType()), '@property string $name'];
        yield [new PropertyTag('name', new StringType(), description: 'Hello'), '@property string $name Hello'];
        yield [new PropertyTag('name', new StringType(), isReadOnly: true), '@property-read string $name'];
        yield [new PropertyTag('name', new StringType(), isWriteOnly: true), '@property-write string $name'];
        yield [new PropertyTag('name', new StringType(), true, true, description: 'Hello'), '@property-read string $name Hello'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
