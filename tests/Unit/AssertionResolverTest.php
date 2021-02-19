<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Identifier\EmptyIdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProvider;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\Page\Page;
use webignition\BasilParser\AssertionParser;
use webignition\BasilResolver\AssertionResolver;

class AssertionResolverTest extends \PHPUnit\Framework\TestCase
{
    private AssertionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = AssertionResolver::createResolver();
    }

    /**
     * @dataProvider resolveAlreadyResolvedDataProvider
     */
    public function testResolveAlreadyResolved(AssertionInterface $assertion): void
    {
        $resolvedAssertion = $this->resolver->resolve(
            $assertion,
            new EmptyPageProvider(),
            new EmptyIdentifierProvider()
        );

        $this->assertSame($assertion, $resolvedAssertion);
    }

    /**
     * @return array[]
     */
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
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider,
        AssertionInterface $expectedAssertion
    ): void {
        $resolvedAssertion = $this->resolver->resolve($assertion, $pageProvider, $identifierProvider);

        $this->assertEquals($expectedAssertion, $resolvedAssertion);
    }

    /**
     * @return array[]
     */
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
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$elements.element_name exists'),
                    '$".selector"'
                ),
            ],
            'exists assertion with page element reference identifier' => [
                'assertion' => $assertionParser->parse('$page_import_name.elements.element_name exists'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com',
                        [
                            'element_name' => '$".selector"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$page_import_name.elements.element_name exists'),
                    '$".selector"'
                ),
            ],
            'is assertion with element reference identifier and literal value' => [
                'assertion' => $assertionParser->parse('$elements.element_name is "value"'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$elements.element_name is "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'is assertion with page element reference identifier and literal value' => [
                'assertion' => $assertionParser->parse('$page_import_name.elements.element_name is "value"'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com',
                        [
                            'element_name' => '$".selector"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$page_import_name.elements.element_name is "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'is assertion with dom identifier and element reference value' => [
                'assertion' => $assertionParser->parse('$".selector" is $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".resolved"',
                ]),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$".selector" is $elements.element_name'),
                    '$".selector"',
                    '$".resolved"'
                ),
            ],
            'is assertion with dom identifier and page element reference value' => [
                'assertion' => $assertionParser->parse('$".selector" is $page_import_name.elements.element_name'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com',
                        [
                            'element_name' => '$".resolved"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$".selector" is $page_import_name.elements.element_name'),
                    '$".selector"',
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
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$elements.element_one is $elements.element_two'),
                    '$".one"',
                    '$".two"'
                ),
            ],
            'is assertion with page element reference identifier and page element reference value' => [
                'assertion' => $assertionParser->parse(
                    '$page_import_name.elements.element_one is $page_import_name.elements.element_two'
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'page_import_name',
                        'http://example.com',
                        [
                            'element_one' => '$".one"',
                            'element_two' => '$".two"',
                        ]
                    ),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse(
                        '$page_import_name.elements.element_one is $page_import_name.elements.element_two'
                    ),
                    '$".one"',
                    '$".two"'
                ),
            ],
        ];
    }
}
