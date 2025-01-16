<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc;
use PHPStan\PhpDocParser\Ast\Type;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser as PhpStanParser;
use Tochka\DocBlockParser\Context\Context;
use Tochka\DocBlockParser\Exception\ParserException;
use Tochka\DocBlockParser\PhpDoc as PhpDocument;
use Tochka\DocBlockParser\Tags\TypeAliasImportTag;
use Tochka\DocBlockParser\Tags\TypeAliasTag;
use Tochka\Types\Alias;
use Tochka\Types\Atomic;
use Tochka\Types\Complex;
use Tochka\Types\Misc\KeyShapeItem;
use Tochka\Types\Misc\KeyShapeItems;
use Tochka\Types\Misc\ShapeItem;
use Tochka\Types\Misc\ShapeItems;
use Tochka\Types\Misc\TemplateType;
use Tochka\Types\NamedTypeInterface;
use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class Parser
{
    public function __construct(
        private FqsenResolver $fqsenResolver = new FqsenResolver(),
    ) {}

    /**
     * @throws ParserException
     */
    public function parse(string $docBlock, ?Context $context = null): PhpDocument
    {
        try {
            $lexer = new Lexer();
            $tokens = new PhpStanParser\TokenIterator($lexer->tokenize($docBlock));

            $constantExprParser = new PhpStanParser\ConstExprParser();
            $typeParser = new PhpStanParser\TypeParser($constantExprParser);
            $phpDocParser = new PhpStanParser\PhpDocParser($typeParser, $constantExprParser);
            $phpDocNode = $phpDocParser->parse($tokens);
        } catch (PhpStanParser\ParserException $exception) {
            throw new ParserException(
                $exception->getCurrentTokenValue(),
                $exception->getCurrentTokenType(),
                $exception->getCurrentOffset(),
                $exception->getExpectedTokenType(),
                $exception->getExpectedTokenValue(),
                $exception->getCurrentTokenLine(),
            );
        }


        $descriptionLines = [];
        $tags = [];

        foreach ($phpDocNode->children as $child) {
            if ($child instanceof PhpDoc\PhpDocTagNode) {
                $tags[] = $this->parseTag($child, $context);
            }
            if ($child instanceof PhpDoc\PhpDocTextNode) {
                $descriptionLines[] = $child->text;
            }
        }

        $description = count($descriptionLines) > 0 ? implode(PHP_EOL, $descriptionLines) : null;

        return new PhpDocument($tags, $description);
    }

    private function parseTag(PhpDoc\PhpDocTagNode $tagNode, ?Context $context): Tags\TagInterface
    {
        $name = $tagNode->name;
        $value = $tagNode->value;

        return match (true) {
            $name === '@api',
            $name === '@psalm-api' => new Tags\ApiTag(),
            $name === '@author' => new Tags\AuthorTag((string) $tagNode->value ?: null),
            $name === '@example' => new Tags\ExampleTag((string) $tagNode->value ?: null),
            $name === '@link' => new Tags\LinkTag((string) $tagNode->value ?: null),
            $name === '@see' => new Tags\SeeTag((string) $tagNode->value ?: null),
            $name === '@since' => new Tags\SinceTag((string) $tagNode->value ?: null),
            $name === '@source' => new Tags\SourceTag((string) $tagNode->value ?: null),
            $name === '@version' => new Tags\VersionTag((string) $tagNode->value ?: null),
            $value instanceof PhpDoc\DeprecatedTagValueNode => new Tags\DeprecatedTag($value->description ?: null),
            $value instanceof PhpDoc\MethodTagValueNode => new Tags\MethodTag(
                $value->methodName,
                $value->isStatic,
                $this->parseMethodParameters($value->parameters, $context),
                $this->parseType($value->returnType, $context),
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\MixinTagValueNode => new Tags\MixinTag(
                $this->parseType($value->type, $context),
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\ParamTagValueNode => new Tags\ParamTag(
                substr($value->parameterName, 1),
                $this->parseType($value->type, $context),
                $value->isReference,
                $value->isVariadic,
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\PropertyTagValueNode => new Tags\PropertyTag(
                substr($value->propertyName, 1),
                $this->parseType($value->type, $context),
                $tagNode->name === '@property-read',
                $tagNode->name === '@property-write',
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\ReturnTagValueNode => new Tags\ReturnTag(
                $this->parseType($value->type, $context),
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\TemplateTagValueNode => new Tags\TemplateTag(
                $value->name,
                $this->parseType($value->bound, $context),
                $this->parseType($value->lowerBound, $context),
                $this->parseType($value->default, $context),
                $name === '@template-covariant',
                $name === '@template-contravariant',
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\ThrowsTagValueNode => new Tags\ThrowsTag(
                $this->parseType($value->type, $context),
                $value->description ?: null,
            ),
            $value instanceof PhpDoc\TypeAliasTagValueNode => new TypeAliasTag(
                $value->alias,
                $this->parseType($value->type, $context),
            ),
            $value instanceof PhpDoc\TypeAliasImportTagValueNode => new TypeAliasImportTag(
                $value->importedAs ?? $value->importedAlias,
                $value->importedAlias,
                $this->fqsenResolver->resolve($value->importedFrom->name, $context),
            ),
            $value instanceof PhpDoc\VarTagValueNode => new Tags\VarTag(
                $this->parseType($value->type, $context),
                substr($value->variableName, 1),
                $value->description ?: null,
            ),
            default => new Tags\UnknownTag($tagNode->name, $tagNode->value),
        };
    }

    /**
     * @param array<PhpDoc\MethodTagValueParameterNode> $parameters
     * @return array<Tags\MethodParameter>
     */
    private function parseMethodParameters(array $parameters, ?Context $context): array
    {
        return array_map(function (PhpDoc\MethodTagValueParameterNode $parameterNode) use ($context) {
            return new Tags\MethodParameter(
                substr($parameterNode->parameterName, 1),
                $this->parseType($parameterNode->type, $context),
                $parameterNode->isReference,
                $parameterNode->isVariadic,
                $parameterNode->defaultValue !== null ? new Tags\ValueConst($parameterNode->defaultValue) : null,
            );
        }, $parameters);
    }

    /**
     * @return ($typeNode is null ? null : TypeInterface)
     */
    private function parseType(?Type\TypeNode $typeNode, ?Context $context): ?TypeInterface
    {
        if ($typeNode === null) {
            return null;
        }

        return match (true) {
            $typeNode instanceof Type\ArrayShapeNode => $this->parseShape($typeNode, $context),
            $typeNode instanceof Type\ArrayTypeNode => new Alias\ListType($this->parseType($typeNode->type, $context)),
            $typeNode instanceof Type\CallableTypeNode => new Atomic\CallableType(),
            $typeNode instanceof Type\ConditionalTypeForParameterNode => Complex\UnionType::mergeTypes(
                $this->parseType($typeNode->if, $context),
                $this->parseType($typeNode->else, $context),
            ),
            $typeNode instanceof Type\ConditionalTypeNode => Complex\UnionType::mergeTypes(
                $this->parseType($typeNode->if, $context),
                $this->parseType($typeNode->else, $context),
            ),
            $typeNode instanceof Type\ConstTypeNode => $this->parseConstType($typeNode),
            $typeNode instanceof Type\GenericTypeNode,
            $typeNode instanceof Type\IdentifierTypeNode => $this->parseIdentifierType($typeNode, $context),
            $typeNode instanceof Type\IntersectionTypeNode => $this->parseIntersectionTypes($typeNode->types, $context),
            $typeNode instanceof Type\NullableTypeNode => $this->parseType($typeNode->type, $context)->setNullable(),
            $typeNode instanceof Type\ObjectShapeNode => $this->parseObjectShape($typeNode, $context),
            $typeNode instanceof Type\UnionTypeNode => $this->parseUnionTypes($typeNode->types, $context),
            default => new Atomic\MixedType(),
        };
    }

    private function parseConstType(Type\ConstTypeNode $constTypeNode): TypeInterface
    {
        $constExpr = $constTypeNode->constExpr;
        return match (true) {
            $constExpr instanceof ConstExprIntegerNode => new Alias\IntConstType((int) $constExpr->value),
            $constExpr instanceof ConstExprFloatNode => new Alias\FloatConstType((float) $constExpr->value),
            $constExpr instanceof ConstExprStringNode => new Alias\StringConstType($constExpr->value),
            default => new Atomic\MixedType(),
        };
    }

    private function parseShape(Type\ArrayShapeNode $arrayShapeNode, ?Context $context): Atomic\ArrayType
    {
        if ($arrayShapeNode->kind === Type\ArrayShapeNode::KIND_ARRAY) {
            return $this->parseArrayShape($arrayShapeNode, $context);
        }

        return $this->parseListShape($arrayShapeNode, $context);
    }

    private function parseArrayShape(Type\ArrayShapeNode $arrayShapeNode, ?Context $context): Atomic\ArrayType
    {
        $items = [];

        $keyShape = false;
        foreach ($arrayShapeNode->items as $itemNode) {
            if ($itemNode->keyName !== null) {
                $keyShape = true;
                break;
            }
        }

        foreach ($arrayShapeNode->items as $key => $itemNode) {
            if ($keyShape) {
                if ($itemNode->keyName instanceof ConstExprStringNode || $itemNode->keyName instanceof ConstExprIntegerNode) {
                    $keyName = $itemNode->keyName->value;
                } elseif ($itemNode->keyName instanceof Type\IdentifierTypeNode) {
                    $keyName = $itemNode->keyName->name;
                } else {
                    $keyName = $key;
                }

                $shapeItem = new KeyShapeItem(
                    (string) $keyName,
                    $this->parseType($itemNode->valueType, $context),
                    $itemNode->optional,
                );
            } else {
                $shapeItem = new ShapeItem(
                    $this->parseType($itemNode->valueType, $context),
                    $itemNode->optional,
                );
            }

            if ($itemNode->keyName instanceof ConstExprIntegerNode) {
                $items[(int) $itemNode->keyName->value] = $shapeItem;
            } else {
                $items[] = $shapeItem;
            }
        }

        if (count($items) === 0) {
            $shapeItems = null;
        } else {
            if (!$keyShape) {
                /** @var array<ShapeItem> $items */
                ksort($items);
                $shapeItems = new ShapeItems(...$items);
            } else {
                /** @var array<KeyShapeItem> $items */
                $shapeItems = new KeyShapeItems(...$items);
            }
        }

        if ($arrayShapeNode->sealed) {
            return new Atomic\ArrayType(new Atomic\NeverType(), new Atomic\NeverType(), $shapeItems);
        }

        if ($arrayShapeNode->unsealedType === null) {
            return new Atomic\ArrayType(shapeItems: $shapeItems);
        }

        return new Atomic\ArrayType(
            $this->parseType($arrayShapeNode->unsealedType->valueType, $context),
            $arrayShapeNode->unsealedType->keyType !== null ? $this->parseType(
                $arrayShapeNode->unsealedType->keyType,
                $context,
            ) : new Alias\ArrayKeyType(),
            $shapeItems,
        );
    }

    private function parseListShape(Type\ArrayShapeNode $arrayShapeNode, ?Context $context): Alias\ListType
    {
        $items = [];

        foreach ($arrayShapeNode->items as $itemNode) {
            $shapeItem = new ShapeItem(
                $this->parseType($itemNode->valueType, $context),
                $itemNode->optional,
            );

            if ($itemNode->keyName instanceof ConstExprIntegerNode) {
                $items[(int) $itemNode->keyName->value] = $shapeItem;
            } else {
                $items[] = $shapeItem;
            }
        }

        if (count($items) === 0) {
            $shapeItems = null;
        } else {
            ksort($items);
            $shapeItems = new ShapeItems(...$items);
        }

        if ($arrayShapeNode->sealed) {
            return new Alias\ListType(new Atomic\NeverType(), $shapeItems);
        }

        if ($arrayShapeNode->unsealedType === null) {
            return new Alias\ListType(shapeItems: $shapeItems);
        }

        return new Alias\ListType(
            $this->parseType($arrayShapeNode->unsealedType->valueType, $context),
            $shapeItems,
        );
    }

    private function parseObjectShape(Type\ObjectShapeNode $objectShapeNode, ?Context $context): Atomic\ObjectType
    {
        $items = [];

        foreach ($objectShapeNode->items as $itemNode) {
            $items[] = new KeyShapeItem(
                (string) $itemNode->keyName,
                $this->parseType($itemNode->valueType, $context),
                $itemNode->optional,
            );
        }

        if (count($items) === 0) {
            return new Atomic\ObjectType();
        }

        return new Atomic\ObjectType(new KeyShapeItems(...$items));
    }

    /**
     * @param array<Type\TypeNode> $typeNodes
     */
    private function parseUnionTypes(array $typeNodes, ?Context $context): Complex\UnionType
    {
        $types = array_map(fn(Type\TypeNode $typeNode) => $this->parseType($typeNode, $context), $typeNodes);

        return Complex\UnionType::mergeTypes(...$types);
    }

    /**
     * @param array<Type\TypeNode> $typeNodes
     */
    private function parseIntersectionTypes(array $typeNodes, ?Context $context): Complex\IntersectType
    {
        $types = array_filter(
            array_map(
                fn(Type\TypeNode $typeNode) => $this->parseType($typeNode, $context),
                $typeNodes,
            ),
            fn(TypeInterface $type) => $type instanceof NamedTypeInterface,
        );

        return new Complex\IntersectType(...$types);
    }

    private function parseIdentifierType(
        Type\GenericTypeNode|Type\IdentifierTypeNode $typeNode,
        ?Context $context,
    ): TypeInterface {
        if ($typeNode instanceof Type\IdentifierTypeNode) {
            $typeNode = new Type\GenericTypeNode($typeNode, []);
        }

        return match (true) {
            $typeNode->type->name === 'array' => $this->parseArray($typeNode->genericTypes, $context),
            $typeNode->type->name === 'bool' => new Atomic\BoolType(),
            $typeNode->type->name === 'callable' => new Atomic\CallableType(),
            $typeNode->type->name === 'float' => new Atomic\FloatType(),
            $typeNode->type->name === 'int' => $this->parseInt($typeNode->genericTypes),
            $typeNode->type->name === 'mixed' => new Atomic\MixedType(),
            $typeNode->type->name === 'never' => new Atomic\NeverType(),
            $typeNode->type->name === 'null' => new Atomic\NullType(),
            $typeNode->type->name === 'object' => new Atomic\ObjectType(),
            $typeNode->type->name === 'resource' => new Atomic\ResourceType(),
            $typeNode->type->name === 'string' => new Atomic\StringType(),
            $typeNode->type->name === 'void' => new Atomic\VoidType(),
            $typeNode->type->name === 'array-key' => new Alias\ArrayKeyType(),
            $typeNode->type->name === 'callable-string' => new Alias\CallableStringType(),
            $typeNode->type->name === 'class-string' => $this->parseClassString($typeNode->genericTypes, $context),
            $typeNode->type->name === 'false' => new Alias\FalseType(),
            $typeNode->type->name === 'int-mask-of' => $this->parseIntMaskOf($typeNode->genericTypes),
            $typeNode->type->name === 'int-mask' => $this->parseIntMask($typeNode->genericTypes),
            $typeNode->type->name === 'iterable' => $this->parseIterable($typeNode->genericTypes, $context),
            $typeNode->type->name === 'list' => $this->parseList($typeNode->genericTypes, $context),
            $typeNode->type->name === 'negative-int' => new Alias\NegativeIntType(),
            $typeNode->type->name === 'non-empty-array' => new Alias\NonEmptyArrayType(),
            $typeNode->type->name === 'non-empty-list' => new Alias\NonEmptyListType(),
            $typeNode->type->name === 'non-empty-string' => new Alias\NonEmptyStringType(),
            $typeNode->type->name === 'non-falsy-string' => new Alias\NonFalsyStringType(),
            $typeNode->type->name === 'non-negative-int' => new Alias\NonNegativeIntType(),
            $typeNode->type->name === 'non-positive-int' => new Alias\NonPositiveIntType(),
            $typeNode->type->name === 'numeric-string' => new Alias\NumericStringType(),
            $typeNode->type->name === 'numeric' => new Alias\NumericType(),
            $typeNode->type->name === 'positive-int' => new Alias\PositiveIntType(),
            $typeNode->type->name === 'scalar' => new Alias\ScalarType(),
            $typeNode->type->name === 'trait-string' => new Alias\TraitStringType(),
            $typeNode->type->name === 'true' => new Alias\TrueType(),
            $typeNode->type->name === 'truthy-string' => new Alias\TruthyStringType(),
            default => $this->parseIdentifier(
                $typeNode->type->name,
                $typeNode->genericTypes,
                $typeNode->variances,
                $context,
            ),
        };
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseArray(array $genericTypes, ?Context $context): Atomic\ArrayType
    {
        return match (count($genericTypes)) {
            0 => new Atomic\ArrayType(),
            1 => new Atomic\ArrayType($this->parseType($genericTypes[0], $context)),
            default => new Atomic\ArrayType(
                $this->parseType($genericTypes[1], $context),
                $this->parseType($genericTypes[0], $context),
            ),
        };
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseIterable(array $genericTypes, ?Context $context): Alias\IterableType
    {
        return match (count($genericTypes)) {
            0 => new Alias\IterableType(),
            1 => new Alias\IterableType($this->parseType($genericTypes[0], $context)),
            default => new Alias\IterableType(
                $this->parseType($genericTypes[1], $context),
                $this->parseType($genericTypes[0], $context),
            ),
        };
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseList(array $genericTypes, ?Context $context): Alias\ListType
    {
        return match (count($genericTypes)) {
            0 => new Alias\ListType(),
            default => new Alias\ListType($this->parseType($genericTypes[0], $context)),
        };
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseInt(array $genericTypes): Atomic\IntType
    {
        if (count($genericTypes) < 2) {
            return new Atomic\IntType();
        }

        $minType = $genericTypes[0];
        if ($minType instanceof Type\ConstTypeNode && $minType->constExpr instanceof ConstExprIntegerNode) {
            $min = (int) $minType->constExpr->value;
        } elseif ($minType instanceof Type\IdentifierTypeNode && $minType->name === 'min') {
            $min = null;
        } else {
            return new Atomic\IntType();
        }

        $maxType = $genericTypes[1];
        if ($maxType instanceof Type\ConstTypeNode && $maxType->constExpr instanceof ConstExprIntegerNode) {
            $max = (int) $maxType->constExpr->value;
        } elseif ($maxType instanceof Type\IdentifierTypeNode && $maxType->name === 'max') {
            $max = null;
        } else {
            return new Atomic\IntType();
        }

        return new Alias\IntRangeType($min, $max);
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseIntMaskOf(array $genericTypes): Alias\IntMaskOfType
    {
        if (count($genericTypes) === 0 || !$genericTypes[0] instanceof Type\ConstTypeNode) {
            return new Alias\IntMaskOfType('*');
        }

        return new Alias\IntMaskOfType((string) $genericTypes[0]);
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseIntMask(array $genericTypes): Atomic\IntType
    {
        $items = [];
        foreach ($genericTypes as $genericType) {
            if ($genericType instanceof Type\ConstTypeNode && $genericType->constExpr instanceof ConstExprIntegerNode) {
                $items[] = (int) $genericType->constExpr->value;
            }
        }

        if (count($items) === 0) {
            return new Atomic\IntType();
        }

        return new Alias\IntMaskType(...$items);
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseClassString(array $genericTypes, ?Context $context): Alias\ClassStringType
    {
        if (count($genericTypes) === 0) {
            return new Alias\ClassStringType();
        }

        $classNameType = $genericTypes[0];
        if (!$classNameType instanceof Type\IdentifierTypeNode) {
            return new Alias\ClassStringType();
        }

        return new Alias\ClassStringType(
            $this->fqsenResolver->resolve($classNameType->name, $context),
        );
    }

    /**
     * @param array<Type\TypeNode> $genericTypes
     */
    private function parseIdentifier(
        string $name,
        array $genericTypes,
        array $variances,
        ?Context $context,
    ): TypeInterface {
        $className = $this->fqsenResolver->resolve($name, $context);

        $generics = null;
        if (count($genericTypes) > 0) {
            $generics = [];
            foreach ($genericTypes as $index => $genericTypeNode) {
                $variance = $variances[$index] ?? Type\GenericTypeNode::VARIANCE_INVARIANT;
                $generics[] = new TemplateType(
                    $this->parseType($genericTypeNode, $context),
                    isCovariant: $variance === Type\GenericTypeNode::VARIANCE_BIVARIANT
                    || $variance === Type\GenericTypeNode::VARIANCE_COVARIANT,
                    isContravariant: $variance === Type\GenericTypeNode::VARIANCE_BIVARIANT
                    || $variance === Type\GenericTypeNode::VARIANCE_CONTRAVARIANT,
                );
            }
        }

        return new Atomic\ClassType($className, $generics);
    }
}
