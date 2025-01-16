<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class MixinTag implements TagInterface
{
    public function __construct(
        public TypeInterface $type,
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        return '@mixin ' . $this->type . ($this->description !== null ? ' ' . $this->description : '');
    }
}
