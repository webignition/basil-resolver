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
        $resolvedIdentifier = $this->resolveValue($identifier, $pageProvider, $identifierProvider);

        if ($resolvedIdentifier !== $identifier) {
            $assertion = $assertion->withIdentifier($resolvedIdentifier);
        }

        if ($assertion instanceof ComparisonAssertionInterface) {
            $value = $assertion->getValue();
            $resolvedValue = $this->resolveValue($value, $pageProvider, $identifierProvider);

            if ($resolvedValue !== $value) {
                $assertion = $assertion->withValue($resolvedValue);
            }
        }

        return $assertion;
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
    private function resolveValue(
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
