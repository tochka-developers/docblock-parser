<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

/**
 * @api
 */
readonly class LinkTag implements TagInterface
{
    public function __construct(
        public ?string $link = null,
    ) {}

    public function __toString(): string
    {
        return '@link' . ($this->link !== null ? ' ' . $this->link : '');
    }
}
