<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use Tochka\Types\TypeInterface;

/**
 * @api
 */
readonly class ReturnTag implements TagInterface
{
    public function __construct(
        public TypeInterface $type,
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        $result = '@return ' . $this->type;

        if ($this->description !== null) {
            $result .= ' ' . $this->description;
        }

        return $result;
    }
}
