<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModels\Page\PageInterface;

class PageResolver
{
    private const PARENT_REFERENCE_NAME_PATTERN = '{{ ([^}\$])+ }}';

    public static function createResolver(): PageResolver
    {
        return new PageResolver();
    }

    /**
     * @param PageInterface $page
     *
     * @return PageInterface
     *
     * @throws UnknownPageElementException
     */
    public function resolve(PageInterface $page): PageInterface
    {
        $identifiers = $page->getIdentifiers();
        $unresolvedIdentifiers = $this->findUnresolvedIdentifiers($identifiers);
        $unresolvedIdentifierCount = count($unresolvedIdentifiers);
        $previousUnresolvedIdentifierCount = null;

        while ($unresolvedIdentifierCount > 0 && $unresolvedIdentifierCount !== $previousUnresolvedIdentifierCount) {
            $resolvedIdentifiers = $this->resolveIdentifiers($identifiers);

            foreach ($identifiers as $name => $identifier) {
                $resolvedIdentifier = $resolvedIdentifiers[$name] ?? $identifier;
                $identifiers[$name] = $resolvedIdentifier;
            }

            $unresolvedIdentifiers = $this->findUnresolvedIdentifiers($identifiers);

            $previousUnresolvedIdentifierCount = $unresolvedIdentifierCount;
            $unresolvedIdentifierCount = count($unresolvedIdentifiers);
        }

        if ($unresolvedIdentifierCount > 0 && $previousUnresolvedIdentifierCount === $unresolvedIdentifierCount) {
            $firstUnresolvedIdentifier = current($unresolvedIdentifiers);
            $unresolvableParentName = $this->findParentName($firstUnresolvedIdentifier);

            throw new UnknownPageElementException($page->getImportName(), $unresolvableParentName);
        }

        return $page->withIdentifiers($identifiers);
    }

    /**
     * @param string[] $identifiers
     *
     * @return string[]
     */
    private function findUnresolvedIdentifiers(array $identifiers): array
    {
        $unresolvedIdentifiers = [];

        foreach ($identifiers as $name => $identifier) {
            if ($this->isUnresolvedIdentifier($identifier)) {
                $unresolvedIdentifiers[$name] = $identifier;
            }
        }

        return $unresolvedIdentifiers;
    }

    /**
     * @param string[] $identifiers
     *
     * @return string[]
     */
    private function resolveIdentifiers(array $identifiers): array
    {
        $resolvedIdentifiers = [];

        foreach ($identifiers as $name => $identifier) {
            if ($this->isUnresolvedIdentifier($identifier)) {
                $parentName = $this->findParentName($identifier);
                if (null === $parentName) {
                    continue;
                }

                $parentIdentifier = $identifiers[$parentName] ?? null;
                if (!is_string($parentIdentifier)) {
                    continue;
                }

                if (false === $this->isUnresolvedIdentifier($parentIdentifier)) {
                    $resolvedIdentifiers[$name] = $this->replaceParentReference(
                        $identifier,
                        $parentName,
                        $parentIdentifier
                    );
                }
            }
        }

        return $resolvedIdentifiers;
    }

    private function isUnresolvedIdentifier(string $identifier): bool
    {
        return preg_match('/' . self::PARENT_REFERENCE_NAME_PATTERN . '/', $identifier) > 0;
    }

    private function findParentName(string $identifier): ?string
    {
        $matches = [];
        $parentNameRegex = '/^' . self::PARENT_REFERENCE_NAME_PATTERN . '/';

        preg_match($parentNameRegex, $identifier, $matches);

        if (count($matches) > 0) {
            return trim($matches[0], '{} ');
        }

        return null;
    }

    private function replaceParentReference(string $identifier, string $parentName, string $parentIdentifier): string
    {
        return str_replace('{{ ' . $parentName . ' }}', '{{ ' . $parentIdentifier . ' }}', $identifier);
    }
}
