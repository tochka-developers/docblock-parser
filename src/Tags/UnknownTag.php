<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;

/**
 * @api
 */
readonly class UnknownTag implements TagInterface
{
    public function __construct(
        public string $name,
        public PhpDocTagValueNode $value,
    ) {}

    public function __toString(): string
    {
        return '@' . $this->name . ' ' . $this->value;
    }
}
