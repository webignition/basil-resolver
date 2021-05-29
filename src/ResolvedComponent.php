<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

class ResolvedComponent implements ResolvedComponentInterface
{
    /**
     * @param ResolvedComponentInterface::TYPE_* $type
     * @param null|string                        $source
     * @param null|string                        $resolved
     */
    public function __construct(
        private string $type,
        private ?string $source,
        private ?string $resolved
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getResolved(): ?string
    {
        return $this->resolved;
    }

    public function isResolved(): bool
    {
        return $this->resolved !== $this->source;
    }
}
