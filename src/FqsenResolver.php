<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser;

use Tochka\DocBlockParser\Context\Context;

/**
 * Resolver for Fqsen using Context information
 *
 * @psalm-immutable
 */
readonly class FqsenResolver
{
    /** @var string Definition of the NAMESPACE operator in PHP */
    private const OPERATOR_NAMESPACE = '\\';

    /**
     * @return class-string
     */
    public function resolve(string $fqsen, ?Context $context = null): string
    {
        if ($context === null) {
            $context = new Context('');
        }

        if ($this->isFqsen($fqsen)) {
            return $fqsen;
        }

        return $this->resolvePartialStructuralElementName($fqsen, $context);
    }

    /**
     * Tests whether the given type is a Fully Qualified Structural Element Name.
     * @psalm-assert-if-true class-string $type
     */
    private function isFqsen(string $type): bool
    {
        return str_starts_with($type, self::OPERATOR_NAMESPACE);
    }

    /**
     * Resolves a partial Structural Element Name (i.e. `Reflection\DocBlock`) to its FQSEN representation
     * (i.e. `\phpDocumentor\Reflection\DocBlock`) based on the Namespace and aliases mentioned in the Context.
     *
     * @return class-string
     */
    private function resolvePartialStructuralElementName(string $type, Context $context): string
    {
        $typeParts = explode(self::OPERATOR_NAMESPACE, $type, 2);

        $namespaceAliases = $context->getNamespaceAliases();

        // if the first segment is not an alias; prepend namespace name and return
        if (!isset($namespaceAliases[$typeParts[0]])) {
            $namespace = $context->getNamespace();
            if ($namespace !== '') {
                $namespace .= self::OPERATOR_NAMESPACE;
            }

            /** @var class-string */
            return self::OPERATOR_NAMESPACE . $namespace . $type;
        }

        $typeParts[0] = $namespaceAliases[$typeParts[0]];

        /** @var class-string */
        return self::OPERATOR_NAMESPACE . implode(self::OPERATOR_NAMESPACE, $typeParts);
    }
}
