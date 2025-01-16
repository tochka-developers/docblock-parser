<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Exception;

use PHPStan\PhpDocParser\Lexer\Lexer;

/**
 * @api
 */
class ParserException extends \RuntimeException
{
    private string $currentTokenValue;
    private int $currentTokenType;
    private int $currentOffset;
    private int $expectedTokenType;
    private ?string $expectedTokenValue;
    private ?int $currentTokenLine;

    public function __construct(
        string $currentTokenValue,
        int $currentTokenType,
        int $currentOffset,
        int $expectedTokenType,
        ?string $expectedTokenValue = null,
        ?int $currentTokenLine = null,
    ) {
        $this->currentTokenValue = $currentTokenValue;
        $this->currentTokenType = $currentTokenType;
        $this->currentOffset = $currentOffset;
        $this->expectedTokenType = $expectedTokenType;
        $this->expectedTokenValue = $expectedTokenValue;
        $this->currentTokenLine = $currentTokenLine;

        parent::__construct(
            sprintf(
                'Unexpected token %s, expected %s%s at offset %d%s',
                $this->formatValue($currentTokenValue),
                Lexer::TOKEN_LABELS[$expectedTokenType],
                $expectedTokenValue !== null ? sprintf(' (%s)', $this->formatValue($expectedTokenValue)) : '',
                $currentOffset,
                $currentTokenLine === null ? '' : sprintf(' on line %d', $currentTokenLine),
            ),
        );
    }

    public function getCurrentTokenValue(): string
    {
        return $this->currentTokenValue;
    }

    public function getCurrentTokenType(): int
    {
        return $this->currentTokenType;
    }

    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }

    public function getExpectedTokenType(): int
    {
        return $this->expectedTokenType;
    }

    public function getExpectedTokenValue(): ?string
    {
        return $this->expectedTokenValue;
    }

    public function getCurrentTokenLine(): ?int
    {
        return $this->currentTokenLine;
    }

    private function formatValue(string $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        assert($json !== false);

        return $json;
    }
}
