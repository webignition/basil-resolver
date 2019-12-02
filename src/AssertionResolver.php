<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\Exception\UnknownIdentifierException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertionInterface;
use webignition\BasilModels\ElementReference\ElementReference;
use webignition\BasilModels\PageElementReference\PageElementReference;

class AssertionResolver
{
    private $pageElementReferenceResolver;

    public function __construct(PageElementReferenceResolver $pageElementReferenceResolver)
    {
        $this->pageElementReferenceResolver = $pageElementReferenceResolver;
    }

    public static function createResolver(): AssertionResolver
    {
        return new AssertionResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
     * @param AssertionInterface $assertion
     * @param PageProviderInterface $pageProvider
     * @param IdentifierProviderInterface $identifierProvider
     *
     * @return AssertionInterface
     *
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownIdentifierException
     */
    public function resolve(
        AssertionInterface $assertion,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider
    ): AssertionInterface {
        $identifier = $assertion->getIdentifier();
        $resolvedIdentifier = $identifier;

        if (ElementReference::is($identifier)) {
            $elementReference = new ElementReference($identifier);
            $resolvedIdentifier = $identifierProvider->findIdentifier($elementReference->getElementName());
        }

        if (PageElementReference::is($identifier)) {
            $resolvedIdentifier = $this->pageElementReferenceResolver->resolve($identifier, $pageProvider);
        }

        if ($resolvedIdentifier !== $identifier) {
            $assertion = $assertion->withIdentifier($resolvedIdentifier);
        }

        if ($assertion instanceof ComparisonAssertionInterface) {
            $value = $assertion->getValue();
            $resolvedValue = $value;

            if (ElementReference::is($value)) {
                $elementReference = new ElementReference($value);
                $resolvedValue = $identifierProvider->findIdentifier($elementReference->getElementName());
            }

            if (PageElementReference::is($value)) {
                $resolvedValue = $this->pageElementReferenceResolver->resolve($value, $pageProvider);
            }

            if ($resolvedValue !== $value) {
                $assertion = $assertion->withValue($resolvedValue);
            }
        }

        return $assertion;
    }
}
