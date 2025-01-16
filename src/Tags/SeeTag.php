<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

/**
 * @api
 */
readonly class SeeTag implements TagInterface
{
    public function __construct(
        public ?string $link = null,
    ) {}

    public function __toString(): string
    {
        return '@see' . ($this->link !== null ? ' ' . $this->link : '');
    }
}
