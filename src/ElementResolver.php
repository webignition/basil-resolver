<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\AttributeReference\AttributeReference;
use webignition\BasilModels\ElementReference\ElementReference;
use webignition\BasilModels\PageElementReference\PageElementReference;

class ElementResolver
{
    private PageElementReferenceResolver $pageElementReferenceResolver;

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
     * @param ProviderInterface $pageProvider
     * @param ProviderInterface $identifierProvider
     *
     * @return string
     *
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownItemException
     */
    public function resolve(
        string $value,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): string {
        try {
            if (ElementReference::is($value)) {
                return $identifierProvider->find((new ElementReference($value))->getElementName());
            }

            if (AttributeReference::is($value)) {
                $attributeReference = new AttributeReference($value);
                $identifier = $identifierProvider->find($attributeReference->getElementName());

                return $identifier . '.' . $attributeReference->getAttributeName();
            }
        } catch (UnknownItemException $unknownIdentifierException) {
            throw new UnknownElementException($unknownIdentifierException->getName());
        }

        if (PageElementReference::is($value)) {
            return $this->pageElementReferenceResolver->resolve($value, $pageProvider);
        }

        return $value;
    }
}
