<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

/**
 * @api
 */
readonly class ApiTag implements TagInterface
{
    public function __toString(): string
    {
        return '@api';
    }
}
