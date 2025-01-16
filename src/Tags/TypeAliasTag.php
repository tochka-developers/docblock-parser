<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

readonly class TypeAliasTag implements TagInterface
{
    public function __construct(
        public string $name,
        public TypeInterface $type,
    ) {}

    public function __toString(): string
    {
        return '@psalm-type ' . $this->name . ' = ' . $this->type;
    }
}
