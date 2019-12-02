<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Identifier\EmptyIdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilModels\Page\Page;
use webignition\BasilParser\AssertionParser;
use webignition\BasilResolver\AssertionResolver;

class AssertionResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AssertionResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = AssertionResolver::createResolver();
    }

    /**
     * @dataProvider resolveAlreadyResolvedDataProvider
     */
    public function testResolveAlreadyResolved(AssertionInterface $assertion)
    {
        $resolvedAssertion = $this->resolver->resolve(
            $assertion,
            new EmptyPageProvider(),
            new EmptyIdentifierProvider()
        );

        $this->assertSame($assertion, $resolvedAssertion);
    }

    public function resolveAlreadyResolvedDataProvider(): array
    {
        $assertionParser = AssertionParser::create();

        return [
            'exists assertion' => [
                'assertion' => $assertionParser->parse('$".selector" exists'),
            ],
            'comparison assertion' => [
                'assertion' => $assertionParser->parse('$".selector" is "value"'),
            ],
        ];
    }

    /**
     * @dataProvider resolveIsResolvedDataProvider
     */
    public function testResolveIsResolved(
        AssertionInterface $assertion,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider,
        AssertionInterface $expectedAssertion
    ) {
        $resolvedAssertion = $this->resolver->resolve($assertion, $pageProvider, $identifierProvider);

        $this->assertEquals($expectedAssertion, $resolvedAssertion);
    }

    public function resolveIsResolvedDataProvider(): array
    {
        $assertionParser = AssertionParser::create();

        return [
            'exists assertion with element reference identifier' => [
                'assertion' => $assertionParser->parse('$elements.element_name exists'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedAssertion' => new Assertion(
                    '$elements.element_name exists',
                    '$".selector"',
                    'exists'
                ),
            ],
            'exists assertion with page element reference identifier' => [
                'assertion' => $assertionParser->parse('$page_import_name.elements.element_name exists'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'element_name' => '$".selector"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new Assertion(
                    '$page_import_name.elements.element_name exists',
                    '$".selector"',
                    'exists'
                ),
            ],
            'is assertion with element reference identifier and literal value' => [
                'assertion' => $assertionParser->parse('$elements.element_name is "value"'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedAssertion' => new ComparisonAssertion(
                    '$elements.element_name is "value"',
                    '$".selector"',
                    'is',
                    '"value"'
                ),
            ],
            'is assertion with page element reference identifier and literal value' => [
                'assertion' => $assertionParser->parse('$page_import_name.elements.element_name is "value"'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'element_name' => '$".selector"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ComparisonAssertion(
                    '$page_import_name.elements.element_name is "value"',
                    '$".selector"',
                    'is',
                    '"value"'
                ),
            ],
            'is assertion with dom identifier and element reference value' => [
                'assertion' => $assertionParser->parse('$".selector" is $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".resolved"',
                ]),
                'expectedAssertion' => new ComparisonAssertion(
                    '$".selector" is $elements.element_name',
                    '$".selector"',
                    'is',
                    '$".resolved"'
                ),
            ],
            'is assertion with dom identifier and page element reference value' => [
                'assertion' => $assertionParser->parse('$".selector" is $page_import_name.elements.element_name'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'element_name' => '$".resolved"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ComparisonAssertion(
                    '$".selector" is $page_import_name.elements.element_name',
                    '$".selector"',
                    'is',
                    '$".resolved"'
                ),
            ],
            'is assertion with element reference identifier and element reference value' => [
                'assertion' => $assertionParser->parse('$elements.element_one is $elements.element_two'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_one' => '$".one"',
                    'element_two' => '$".two"',
                ]),
                'expectedAssertion' => new ComparisonAssertion(
                    '$elements.element_one is $elements.element_two',
                    '$".one"',
                    'is',
                    '$".two"'
                ),
            ],
            'is assertion with page element reference identifier and page element reference value' => [
                'assertion' => $assertionParser->parse(
                    '$page_import_name.elements.element_one is $page_import_name.elements.element_two'
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'element_one' => '$".one"',
                            'element_two' => '$".two"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ComparisonAssertion(
                    '$page_import_name.elements.element_one is $page_import_name.elements.element_two',
                    '$".one"',
                    'is',
                    '$".two"'
                ),
            ],
        ];
    }
}
