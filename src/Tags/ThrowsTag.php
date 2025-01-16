<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class ThrowsTag implements TagInterface
{
    public function __construct(
        public TypeInterface $type,
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        return '@throws ' . $this->type . ($this->description !== null ? ' ' . $this->description : '');
    }
}
