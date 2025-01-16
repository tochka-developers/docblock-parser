<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\TemplateTag;
use Tochka\Types\Atomic\StringType;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(TemplateTag::class)]
class TemplateTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [new TemplateTag('TName'), '@template TName'];
        yield [new TemplateTag('TName', bound: new StringType()), '@template TName of string'];
        yield [new TemplateTag('TName', lowerBound: new StringType()), '@template TName super string'];
        yield [new TemplateTag('TName', default: new StringType()), '@template TName = string'];
        yield [new TemplateTag('TName', isCovariant: true), '@template-covariant TName'];
        yield [new TemplateTag('TName', isContravariant: true), '@template-contravariant TName'];
        yield [new TemplateTag('TName', description: 'Hello'), '@template TName Hello'];

        yield [
            new TemplateTag(
                'TName',
                bound: new StringType(),
                default: new StringType(),
                isCovariant: true,
                description: 'Hello',
            ),
            '@template-covariant TName of string = string Hello',
        ];

        yield [
            new TemplateTag(
                'TName',
                lowerBound: new StringType(),
                default: new StringType(),
                isContravariant: true,
                description: 'Hello',
            ),
            '@template-contravariant TName super string = string Hello',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
