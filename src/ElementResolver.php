<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownIdentifierException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\ElementReference\ElementReference;
use webignition\BasilModels\PageElementReference\PageElementReference;

class ElementResolver
{
    private $pageElementReferenceResolver;

    public function __construct(PageElementReferenceResolver $pageElementReferenceResolver)
    {
        $this->pageElementReferenceResolver = $pageElementReferenceResolver;
    }

    public static function createResolver(): ElementResolver
    {
        return new ElementResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
     * @param string $value
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return string
     *
     * @throws UnknownIdentifierException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        string $value,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): string {
        if (ElementReference::is($value)) {
            return $identifierProvider->findIdentifier((new ElementReference($value))->getElementName());
        }

        if (PageElementReference::is($value)) {
            return $this->pageElementReferenceResolver->resolve($value, $pageProvider);
        }

        return $value;
    }
}
