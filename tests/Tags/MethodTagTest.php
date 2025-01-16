<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\MethodParameter;
use Tochka\DocBlockParser\Tags\MethodTag;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\Types\Atomic\IntType;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(MethodTag::class)]
class MethodTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        $parameters = [
            new MethodParameter('foo', new StringType()),
            new MethodParameter('bar', new IntType(), isVariadic: true),
        ];
        yield [new MethodTag('name'), '@method name()'];
        yield [new MethodTag('name', isStatic: true), '@method static name()'];
        yield [new MethodTag('name', parameters: $parameters), '@method name(string $foo, int ...$bar)'];
        yield [new MethodTag('name', returnType: new StringType()), '@method string name()'];
        yield [new MethodTag('name', description: 'Hello'), '@method name() Hello'];
        yield [
            new MethodTag(
                'name',
                true,
                $parameters,
                new StringType(),
                'Hello',
            ),
            '@method static string name(string $foo, int ...$bar) Hello',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
