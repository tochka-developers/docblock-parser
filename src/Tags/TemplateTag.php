<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class TemplateTag implements TagInterface
{
    public function __construct(
        public string $name,
        public ?TypeInterface $bound = null,
        public ?TypeInterface $lowerBound = null,
        public ?TypeInterface $default = null,
        public bool $isCovariant = false,
        public bool $isContravariant = false,
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        if ($this->isCovariant) {
            $result = '@template-covariant';
        } elseif ($this->isContravariant) {
            $result = '@template-contravariant';
        } else {
            $result = '@template';
        }

        $result .= ' ' . $this->name;
        if ($this->bound !== null) {
            $result .= ' of ' . $this->bound;
        } elseif ($this->lowerBound !== null) {
            $result .= ' super ' . $this->lowerBound;
        }

        if ($this->default !== null) {
            $result .= ' = ' . $this->default;
        }

        if ($this->description !== null) {
            $result .= ' ' . $this->description;
        }

        return $result;
    }
}
