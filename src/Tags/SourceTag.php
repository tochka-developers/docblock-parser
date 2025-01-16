<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

/**
 * @api
 */
readonly class SourceTag implements TagInterface
{
    public function __construct(
        public ?string $description = null,
    ) {}

    public function __toString(): string
    {
        return '@source' . ($this->description !== null ? ' ' . $this->description : '');
    }
}
