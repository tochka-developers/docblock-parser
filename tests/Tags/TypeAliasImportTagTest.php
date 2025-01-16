<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser\Tags;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Tags\TagInterface;
use Tochka\DocBlockParser\Tags\TypeAliasImportTag;
use TochkaTest\DocBlockParser\TestCase;

#[CoversClass(TypeAliasImportTag::class)]
class TypeAliasImportTagTest extends TestCase
{
    public static function dataProvider(): iterable
    {
        yield [
            new TypeAliasImportTag('MyAlias', 'ImportedType', 'FromClass'),
            '@psalm-import-type ImportedType from FromClass as MyAlias',
        ];

        yield [
            new TypeAliasImportTag('ImportedType', 'ImportedType', 'FromClass'),
            '@psalm-import-type ImportedType from FromClass',
        ];
    }

    #[DataProvider('dataProvider')]
    public function testToString(TagInterface $tag, string $expected): void
    {
        self::assertEquals($expected, (string) $tag);
    }
}
