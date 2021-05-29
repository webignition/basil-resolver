<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

interface ResolvedComponentInterface
{
    public const TYPE_IDENTIFIER = 'identifier';
    public const TYPE_VALUE = 'value';

    /**
     * @return self::TYPE_*
     */
    public function getType(): string;

    public function getSource(): ?string;

    public function getResolved(): ?string;

    public function isResolved(): bool;
}
