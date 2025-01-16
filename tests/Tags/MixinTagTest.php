<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\MixinTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\Types\Atomic\ClassType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(MixinTag::class)]
class MixinTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new MixinTag(new ClassType('HelloClass')), '@mixin HelloClass'];
        yield [new MixinTag(new ClassType('HelloClass'), 'Description'), '@mixin HelloClass Description'];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
