<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class VarTag implements TagInterface
{
    public function __construct(
        public TypeInterface $type,
        public ?string $name = null,
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        $result = '@var ' . $this->type;

        if ($this->name !== null) {
            $result .= ' $' . $this->name;
        }

        if ($this->description !== null) {
            $result .= ' ' . $this->description;
        }

        return $result;
    }
}
