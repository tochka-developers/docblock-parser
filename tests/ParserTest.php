<?php

declare(strict_types=1);

namespace TochkaTest\DocBlockParser;

use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tochka\DocBlockParser\Context\ContextFactory;
use Tochka\DocBlockParser\Parser;
use Tochka\DocBlockParser\Tags;
use Tochka\Types\Alias;
use Tochka\Types\Atomic;
use Tochka\Types\Complex\IntersectType;
use Tochka\Types\Complex\UnionType;
use Tochka\Types\Misc\KeyShapeItem;
use Tochka\Types\Misc\KeyShapeItems;
use Tochka\Types\Misc\ShapeItem;
use Tochka\Types\Misc\ShapeItems;
use Tochka\Types\Misc\TemplateType;
use Tochka\Types\TypeInterface;

#[CoversClass(Parser::class)]
class ParserTest extends TestCase
{
    public static function parseTagsProvider(): iterable
    {
        yield ['@api', new Tags\ApiTag()];
        yield ['@psalm-api', new Tags\ApiTag()];

        yield ['@author', new Tags\AuthorTag()];
        yield ['@author Hello', new Tags\AuthorTag('Hello')];

        yield ['@deprecated', new Tags\DeprecatedTag()];
        yield ['@deprecated Hello', new Tags\DeprecatedTag('Hello')];

        yield ['@example', new Tags\ExampleTag()];
        yield ['@example Hello', new Tags\ExampleTag('Hello')];

        yield ['@link', new Tags\LinkTag()];
        yield ['@link Hello', new Tags\LinkTag('Hello')];

        yield ['@method test()', new Tags\MethodTag('test')];
        yield ['@method bool test()', new Tags\MethodTag('test', returnType: new Atomic\BoolType())];
        yield ['@method static bool test()', new Tags\MethodTag('test', isStatic: true, returnType: new Atomic\BoolType())];
        yield [
            '@method static bool test(bool $foo, bool &$link, bool ...$bar) Hello',
            new Tags\MethodTag(
                'test',
                true,
                [
                    new Tags\MethodParameter('foo', new Atomic\BoolType()),
                    new Tags\MethodParameter('link', new Atomic\BoolType(), isReference: true),
                    new Tags\MethodParameter('bar', new Atomic\BoolType(), isVariadic: true),
                ],
                new Atomic\BoolType(),
                'Hello',
            ),
        ];

        yield ['@mixin bool', new Tags\MixinTag(new Atomic\BoolType())];
        yield ['@mixin bool Hello', new Tags\MixinTag(new Atomic\BoolType(), 'Hello')];

        yield ['@param bool $name', new Tags\ParamTag('name', new Atomic\BoolType())];
        yield ['@param bool $name Hello', new Tags\ParamTag('name', new Atomic\BoolType(), description: 'Hello')];
        yield [
            '@param bool &$name Hello',
            new Tags\ParamTag('name', new Atomic\BoolType(), isReference: true, description: 'Hello'),
        ];
        yield [
            '@param bool ...$name Hello',
            new Tags\ParamTag('name', new Atomic\BoolType(), isVariadic: true, description: 'Hello'),
        ];

        yield ['@property bool $name', new Tags\PropertyTag('name', new Atomic\BoolType())];
        yield ['@property-read bool $name', new Tags\PropertyTag('name', new Atomic\BoolType(), isReadOnly: true)];
        yield ['@property-write bool $name', new Tags\PropertyTag('name', new Atomic\BoolType(), isWriteOnly: true)];
        yield ['@property bool $name Hello', new Tags\PropertyTag('name', new Atomic\BoolType(), description: 'Hello')];
        yield ['@property-read bool $name Hello', new Tags\PropertyTag('name', new Atomic\BoolType(), isReadOnly: true, description: 'Hello')];
        yield ['@property-write bool $name Hello', new Tags\PropertyTag('name', new Atomic\BoolType(), isWriteOnly: true, description: 'Hello')];

        yield ['@return bool', new Tags\ReturnTag(new Atomic\BoolType())];
        yield ['@return bool Hello', new Tags\ReturnTag(new Atomic\BoolType(), 'Hello')];

        yield ['@see', new Tags\SeeTag()];
        yield ['@see Hello', new Tags\SeeTag('Hello')];

        yield ['@since', new Tags\SinceTag()];
        yield ['@since Hello', new Tags\SinceTag('Hello')];

        yield ['@source', new Tags\SourceTag()];
        yield ['@source Hello', new Tags\SourceTag('Hello')];

        yield ['@template TValue', new Tags\TemplateTag('TValue')];
        yield [
            '@template TValue of bool = bool Hello',
            new Tags\TemplateTag('TValue', bound: new Atomic\BoolType(), default: new Atomic\BoolType(), description: 'Hello'),
        ];
        yield [
            '@template-covariant TValue of bool = bool Hello',
            new Tags\TemplateTag(
                'TValue',
                bound: new Atomic\BoolType(),
                default: new Atomic\BoolType(),
                isCovariant: true,
                description: 'Hello',
            ),
        ];
        yield [
            '@template-contravariant TValue super bool = bool Hello',
            new Tags\TemplateTag(
                'TValue',
                lowerBound: new Atomic\BoolType(),
                default: new Atomic\BoolType(),
                isContravariant: true,
                description: 'Hello',
            ),
        ];

        yield ['@throws bool', new Tags\ThrowsTag(new Atomic\BoolType())];
        yield ['@throws bool Hello', new Tags\ThrowsTag(new Atomic\BoolType(), 'Hello')];

        yield ['@psalm-type Test = int<1,10>', new Tags\TypeAliasTag('Test', new Alias\IntRangeType(1, 10))];
        yield ['@phpstan-type Test = int<1,10>', new Tags\TypeAliasTag('Test', new Alias\IntRangeType(1, 10))];

        yield ['@psalm-import-type Foo from Hello', new Tags\TypeAliasImportTag('Foo', 'Foo', '\\Hello')];
        yield ['@psalm-import-type Foo from Hello as Bar', new Tags\TypeAliasImportTag('Bar', 'Foo', '\\Hello')];
        yield ['@phpstan-import-type Foo from Hello', new Tags\TypeAliasImportTag('Foo', 'Foo', '\\Hello')];
        yield ['@phpstan-import-type Foo from Hello as Bar', new Tags\TypeAliasImportTag('Bar', 'Foo', '\\Hello')];

        yield ['@var bool', new Tags\VarTag(new Atomic\BoolType())];
        yield ['@var bool $name', new Tags\VarTag(new Atomic\BoolType(), 'name')];
        yield ['@var bool $name Hello', new Tags\VarTag(new Atomic\BoolType(), 'name', 'Hello')];

        yield ['@version', new Tags\VersionTag()];
        yield ['@version Hello', new Tags\VersionTag('Hello')];

        yield ['@custom bool Hello', new Tags\UnknownTag('@custom', new GenericTagValueNode('bool Hello'))];
    }

    #[DataProvider('parseTagsProvider')]
    public function testParseTags(string $docBlock, Tags\TagInterface $tag): void
    {
        $parser = new Parser();
        self::assertEquals([$tag], $parser->parse('/** ' . $docBlock . ' */')->getTags());
    }

    public static function parseTypeProvider(): iterable
    {
        yield ['array', new Atomic\ArrayType()];
        yield ['array<int>', new Atomic\ArrayType(new Atomic\IntType())];
        yield ['array<int, bool>', new Atomic\ArrayType(new Atomic\BoolType(), new Atomic\IntType())];
        yield ['array{}', new Atomic\ArrayType(new Atomic\NeverType(), new Atomic\NeverType())];
        yield [
            'array{foo: string, bar?: int}',
            new Atomic\ArrayType(
                new Atomic\NeverType(),
                new Atomic\NeverType(),
                new KeyShapeItems(
                    new KeyShapeItem('foo', new Atomic\StringType()),
                    new KeyShapeItem('bar', new Atomic\IntType(), true),
                ),
            ),
        ];
        yield [
            'array{foo: string, bar?: int, ...}',
            new Atomic\ArrayType(
                new Atomic\MixedType(),
                new Alias\ArrayKeyType(),
                new KeyShapeItems(
                    new KeyShapeItem('foo', new Atomic\StringType()),
                    new KeyShapeItem('bar', new Atomic\IntType(), true),
                ),
            ),
        ];
        yield [
            'array{foo: string, bar?: int, ...<int, string>}',
            new Atomic\ArrayType(
                new Atomic\StringType(),
                new Atomic\IntType(),
                new KeyShapeItems(
                    new KeyShapeItem('foo', new Atomic\StringType()),
                    new KeyShapeItem('bar', new Atomic\IntType(), true),
                ),
            ),
        ];
        yield [
            'array{string, int, ...<string>}',
            new Atomic\ArrayType(
                new Atomic\StringType(),
                new Alias\ArrayKeyType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType()),
                ),
            ),
        ];

        yield ['bool', new Atomic\BoolType()];
        yield ['callable', new Atomic\CallableType()];

        yield ['\\' . Tags\ApiTag::class, new Atomic\ClassType('\\' . Tags\ApiTag::class)];
        yield ['Tags\ApiTag', new Atomic\ClassType('\\' . Tags\ApiTag::class)];
        yield [
            '\\' . Tags\ApiTag::class . '<string, int>',
            new Atomic\ClassType(
                '\\' . Tags\ApiTag::class,
                [
                    new TemplateType(new Atomic\StringType()),
                    new TemplateType(new Atomic\IntType()),
                ],
            ),
        ];
        yield [
            '\\' . Tags\ApiTag::class . '<*, covariant string, contravariant int>',
            new Atomic\ClassType(
                '\\' . Tags\ApiTag::class,
                [
                    new TemplateType(new Atomic\MixedType(), true, true),
                    new TemplateType(new Atomic\StringType(), isCovariant: true),
                    new TemplateType(new Atomic\IntType(), isContravariant: true),
                ],
            ),
        ];

        yield ['float', new Atomic\FloatType()];
        yield ['int', new Atomic\IntType()];
        yield ['mixed', new Atomic\MixedType()];
        yield ['never', new Atomic\NeverType()];
        yield ['null', new Atomic\NullType()];

        yield ['object', new Atomic\ObjectType()];
        yield [
            'object{foo: string, bar?: int}',
            new Atomic\ObjectType(
                new KeyShapeItems(
                    new KeyShapeItem('foo', new Atomic\StringType()),
                    new KeyShapeItem('bar', new Atomic\IntType(), true),
                ),
            ),
        ];
        yield ['resource', new Atomic\ResourceType()];
        yield ['string', new Atomic\StringType()];
        yield ['void', new Atomic\VoidType()];

        yield ['array-key', new Alias\ArrayKeyType()];
        yield ['callable-string', new Alias\CallableStringType()];
        yield ['class-string', new Alias\ClassStringType()];
        yield ['class-string<\\' . Tags\ApiTag::class . '>', new Alias\ClassStringType('\\' . Tags\ApiTag::class)];
        yield ['class-string<Tags\ApiTag>', new Alias\ClassStringType('\\' . Tags\ApiTag::class)];
        yield ['false', new Alias\FalseType()];
        yield ['1.23', new Alias\FloatConstType(1.23)];
        yield ['123', new Alias\IntConstType(123)];
        yield ['int-mask-of<MyClass::CONST_*>', new Alias\IntMaskOfType('MyClass::CONST_*')];
        yield ['int-mask<1,2,4>', new Alias\IntMaskType(1, 2, 4)];

        yield ['int<1, 100>', new Alias\IntRangeType(1, 100)];
        yield ['int<1, max>', new Alias\IntRangeType(1)];
        yield ['int<min, 100>', new Alias\IntRangeType(rangeMax: 100)];

        yield ['iterable', new Alias\IterableType()];
        yield ['iterable<string>', new Alias\IterableType(new Atomic\StringType())];
        yield ['iterable<string, object>', new Alias\IterableType(new Atomic\ObjectType(), new Atomic\StringType())];

        yield ['list', new Alias\ListType()];
        yield ['list<string>', new Alias\ListType(new Atomic\StringType())];
        yield ['list{}', new Alias\ListType(new Atomic\NeverType())];
        yield [
            'list{string, int}',
            new Alias\ListType(
                new Atomic\NeverType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType()),
                ),
            ),
        ];
        yield [
            'list{string, int, ...}',
            new Alias\ListType(
                new Atomic\MixedType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType()),
                ),
            ),
        ];
        yield [
            'list{string, int, ...<string>}',
            new Alias\ListType(
                new Atomic\StringType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType()),
                ),
            ),
        ];
        yield [
            'list{0: string, 1?: int}',
            new Alias\ListType(
                new Atomic\NeverType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType(), true),
                ),
            ),
        ];
        yield [
            'list{0: string, 1?: int, ...}',
            new Alias\ListType(
                new Atomic\MixedType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType(), true),
                ),
            ),
        ];
        yield [
            'list{0: string, 1?: int, ...<string>}',
            new Alias\ListType(
                new Atomic\StringType(),
                new ShapeItems(
                    new ShapeItem(new Atomic\StringType()),
                    new ShapeItem(new Atomic\IntType(), true),
                ),
            ),
        ];

        yield ['negative-int', new Alias\NegativeIntType()];
        yield ['non-empty-array', new Alias\NonEmptyArrayType()];
        yield ['non-empty-list', new Alias\NonEmptyListType()];
        yield ['non-empty-string', new Alias\NonEmptyStringType()];
        yield ['non-falsy-string', new Alias\NonFalsyStringType()];
        yield ['non-negative-int', new Alias\NonNegativeIntType()];
        yield ['non-positive-int', new Alias\NonPositiveIntType()];
        yield ['numeric-string', new Alias\NumericStringType()];
        yield ['numeric', new Alias\NumericType()];
        yield ['positive-int', new Alias\PositiveIntType()];
        yield ['scalar', new Alias\ScalarType()];
        yield ['\'string\'', new Alias\StringConstType('string')];
        yield ['trait-string', new Alias\TraitStringType()];
        yield ['true', new Alias\TrueType()];
        yield ['truthy-string', new Alias\TruthyStringType()];

        yield ['string|int|bool', new UnionType(new Atomic\StringType(), new Atomic\IntType(), new Atomic\BoolType())];
        yield [
            'string|int<1,20>|array<string, bool>',
            new UnionType(
                new Atomic\StringType(),
                new Alias\IntRangeType(1, 20),
                new Atomic\ArrayType(
                    new Atomic\BoolType(),
                    new Atomic\StringType(),
                ),
            ),
        ];
        yield ['string&int&bool', new IntersectType(new Atomic\StringType(), new Atomic\IntType(), new Atomic\BoolType())];
        yield [
            'string&int<1,20>&array<string, bool>',
            new IntersectType(
                new Atomic\StringType(),
                new Alias\IntRangeType(1, 20),
                new Atomic\ArrayType(
                    new Atomic\BoolType(),
                    new Atomic\StringType(),
                ),
            ),
        ];
    }

    #[DataProvider('parseTypeProvider')]
    public function testParseType(string $type, TypeInterface $expectedType): void
    {
        $docBlock = "/** @property {$type} \$property */";

        $parser = new Parser();
        $contextFactory = new ContextFactory();
        $tags = $parser->parse($docBlock, $contextFactory->createFromReflector(new \ReflectionClass($this)));

        self::assertEquals($expectedType, $tags->firstTagByType(Tags\PropertyTag::class)->type);
    }
}
