<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

class ResolvedComponent
{
    /**
     * @param null|string $source
     * @param null|string $resolved
     */
    public function __construct(
        private ?string $source,
        private ?string $resolved
    ) {
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
