<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Identifier\EmptyIdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProviderInterface;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\Page\Page;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilResolver\StatementResolver;

class StatementResolverTest extends \PHPUnit\Framework\TestCase
{
    private StatementResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = StatementResolver::createResolver();
    }

    /**
     * @dataProvider resolveAlreadyResolvedActionDataProvider
     * @dataProvider resolveAlreadyResolvedAssertionDataProvider
     */
    public function testResolveAlreadyResolved(StatementInterface $statement): void
    {
        $resolvedStatement = $this->resolver->resolve(
            $statement,
            new EmptyPageProvider(),
            new EmptyIdentifierProvider()
        );

        $this->assertSame($statement, $resolvedStatement);
    }

    /**
     * @return array<mixed>
     */
    public function resolveAlreadyResolvedActionDataProvider(): array
    {
        $actionParser = ActionParser::create();

        return [
            'interaction action' => [
                'statement' => $actionParser->parse('click $".selector"'),
            ],
            'input action' => [
                'statement' => $actionParser->parse('set $".selector" to "value"'),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function resolveAlreadyResolvedAssertionDataProvider(): array
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
     * @dataProvider resolveIsResolvedActionDataProvider
     * @dataProvider resolveIsResolvedAssertionDataProvider
     */
    public function testResolveIsResolved(
        StatementInterface $statement,
        PageProviderInterface $pageProvider,
        IdentifierProviderInterface $identifierProvider,
        StatementInterface $expectedStatement
    ): void {
        $resolvedAssertion = $this->resolver->resolve($statement, $pageProvider, $identifierProvider);

        $this->assertEquals($expectedStatement, $resolvedAssertion);
    }

    /**
     * @return array<mixed>
     */
    public function resolveIsResolvedActionDataProvider(): array
    {
        $actionParser = ActionParser::create();

        return [
            'interaction action with element reference identifier' => [
                'statement' => $actionParser->parse('click $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('click $elements.element_name'),
                    '$".selector"'
                ),
            ],
            'interaction action with page element reference identifier' => [
                'statement' => $actionParser->parse('click $page_import_name.elements.element_name'),
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
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('click $page_import_name.elements.element_name'),
                    '$".selector"'
                ),
            ],
            'input action with element reference identifier and literal value' => [
                'statement' => $actionParser->parse('set $elements.element_name to "value"'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $elements.element_name to "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'input action with page element reference identifier and literal value' => [
                'statement' => $actionParser->parse('set $page_import_name.elements.element_name to "value"'),
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
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $page_import_name.elements.element_name to "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'input action with dom identifier and element reference value' => [
                'statement' => $actionParser->parse('set $".selector" to $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".resolved"',
                ]),
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $elements.element_name'),
                    '$".selector"',
                    '$".resolved"'
                ),
            ],
            'input action with dom identifier and page element reference value' => [
                'statement' => $actionParser->parse('set $".selector" to $page_import_name.elements.element_name'),
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
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $page_import_name.elements.element_name'),
                    '$".selector"',
                    '$".resolved"'
                ),
            ],
            'input action with element reference identifier and element reference value' => [
                'statement' => $actionParser->parse('set $elements.element_one to $elements.element_two'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_one' => '$".one"',
                    'element_two' => '$".two"',
                ]),
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $elements.element_one to $elements.element_two'),
                    '$".one"',
                    '$".two"'
                ),
            ],
            'input action with page element reference identifier and page element reference value' => [
                'statement' => $actionParser->parse(
                    'set $page_import_name.elements.element_one to $page_import_name.elements.element_two'
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
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse(
                        'set $page_import_name.elements.element_one to $page_import_name.elements.element_two'
                    ),
                    '$".one"',
                    '$".two"'
                ),
            ],
            'input action with dom identifier and imported page url value' => [
                'statement' => $actionParser->parse('set $".selector" to $page_import_name.url'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com'),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedStatement' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $page_import_name.url'),
                    '$".selector"',
                    '"http://example.com"'
                ),
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function resolveIsResolvedAssertionDataProvider(): array
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
            'is assertion with literal identifier and imported page url value' => [
                'assertion' => $assertionParser->parse('$page.url is $page_import_name.url'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com'),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$page.url is $page_import_name.url'),
                    '$page.url',
                    '"http://example.com"'
                ),
            ],
            'is assertion with page url identifier and literal value' => [
                'assertion' => $assertionParser->parse('$page_import_name.url is "http://example.com"'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com'),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAssertion' => new ResolvedAssertion(
                    $assertionParser->parse('$page_import_name.url is "http://example.com"'),
                    '"http://example.com"',
                    '"http://example.com"'
                ),
            ],
        ];
    }
}
