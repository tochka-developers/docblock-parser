<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser\Tags;

readonly class TypeAliasImportTag implements TagInterface
{
    public function __construct(
        public string $name,
        public string $importedName,
        public string $importedFrom,
    ) {}

    public function __toString(): string
    {
        $result = '@psalm-import-type ' . $this->importedName . ' from ' . $this->importedFrom;
        if ($this->importedName !== $this->name) {
            $result .= ' as ' . $this->name;
        }

        return $result;
    }
}
