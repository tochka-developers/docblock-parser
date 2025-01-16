<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

/**
 * @api
 */
readonly class SinceTag implements TagInterface
{
    public function __construct(
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        return '@since' . ($this->description !== null ? ' ' . $this->description : '');
    }
}
