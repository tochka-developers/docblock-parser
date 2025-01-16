<?php

declare(strict_types=1);

namespace Tochka\DocBlockParser;

use Tochka\DocBlockParser\Tags\TagInterface;

/**
 * @api
 */
readonly class PhpDoc
{
    /**
     * @param list<TagInterface> $tags
     */
    public function __construct(
        private array $tags,
        private ?string $description,
    ) {}

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return list<TagInterface>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param class-string<TagInterface> $type
     */
    public function hasTagsByType(string $type): bool
    {
        foreach ($this->tags as $tag) {
            if (is_a($tag, $type, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template TTag of TagInterface
     * @param class-string<TTag> $type
     * @return list<TTag>
     */
    public function getTagsByType(string $type): array
    {
        return array_values(
            array_filter(
                $this->tags,
                fn(TagInterface $tag) => is_a($tag, $type, true),
            ),
        );
    }

    /**
     * @template TTag of TagInterface
     * @param class-string<TTag> $type
     * @psalm-return TTag|null
     */
    public function firstTagByType(string $type): ?TagInterface
    {
        foreach ($this->tags as $tag) {
            if (is_a($tag, $type, true)) {
                return $tag;
            }
        }

        return null;
    }
}
