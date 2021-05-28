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
    public function __construct(
        private PageElementReferenceResolver $pageElementReferenceResolver
    ) {
    }

    public static function createResolver(): ElementResolver
    {
        return new ElementResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
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
