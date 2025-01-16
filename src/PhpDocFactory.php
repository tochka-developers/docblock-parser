<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser;

use Tochka\DocBlockParser\Context\ContextFactory;

/**
 * @api
 */
readonly class PhpDocFactory
{
    public function __construct(
        private Parser $docBlockParser,
        private ContextFactory $contextFactory,
    ) {}

    public function getPhpDocFromReflector(\Reflector $reflector): ?PhpDoc
    {
        try {
            if (
                !$reflector instanceof \ReflectionClassConstant
                && !$reflector instanceof \ReflectionClass
                && !$reflector instanceof \ReflectionFunctionAbstract
                && !$reflector instanceof \ReflectionProperty
            ) {
                return null;
            }

            return $this->docBlockParser->parse(
                $reflector->getDocComment(),
                $this->contextFactory->createFromReflector($reflector),
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
