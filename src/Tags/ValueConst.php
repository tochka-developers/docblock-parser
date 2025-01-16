<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayItemNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;

/**
 * @api
 */
readonly class ValueConst implements \Stringable
{
    public function __construct(
        public ConstExprNode $value,
    ) {}

    public function __toString(): string
    {
        return $this->constExprToString($this->value);
    }

    private function constExprToString(ConstExprNode $node): string
    {
        if ($node instanceof ConstExprStringNode) {
            return "'" . $node->value . "'";
        }

        if ($node instanceof ConstExprArrayNode) {
            $values = array_map(function (ConstExprArrayItemNode $item) {
                if ($item->key !== null) {
                    return $this->constExprToString($item->key) . ' => ' . $this->constExprToString($item->value);
                }

                return $this->constExprToString($item->value);
            }, $node->items);

            return '[' . implode(', ', $values) . ']';
        }

        return (string) $node;
    }
}
