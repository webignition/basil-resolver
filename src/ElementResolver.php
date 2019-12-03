<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownIdentifierException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\AttributeReference\AttributeReference;
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
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        string $value,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): string {
        try {
            if (ElementReference::is($value)) {
                return $identifierProvider->findIdentifier((new ElementReference($value))->getElementName());
            }

            if (AttributeReference::is($value)) {
                $attributeReference = new AttributeReference($value);
                $identifier = $identifierProvider->findIdentifier($attributeReference->getElementName());

                return $identifier . '.' . $attributeReference->getAttributeName();
            }
        } catch (UnknownIdentifierException $unknownIdentifierException) {
            throw new UnknownElementException($unknownIdentifierException->getName());
        }

        if (PageElementReference::is($value)) {
            return $this->pageElementReferenceResolver->resolve($value, $pageProvider);
        }

        return $value;
    }
}
