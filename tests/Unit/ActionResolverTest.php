<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilModelProvider\Identifier\EmptyIdentifierProvider;
use webignition\BasilModelProvider\Identifier\IdentifierProvider;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Action\ActionInterface;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Page\Page;
use webignition\BasilParser\ActionParser;
use webignition\BasilResolver\ActionResolver;

class ActionResolverTest extends \PHPUnit\Framework\TestCase
{
    private ActionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = ActionResolver::createResolver();
    }

    /**
     * @dataProvider resolveAlreadyResolvedDataProvider
     */
    public function testResolveAlreadyResolved(ActionInterface $assertion): void
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
        $actionParser = ActionParser::create();

        return [
            'interaction action' => [
                'action' => $actionParser->parse('click $".selector"'),
            ],
            'input action' => [
                'action' => $actionParser->parse('set $".selector" to "value"'),
            ],
        ];
    }

    /**
     * @dataProvider resolveIsResolvedDataProvider
     */
    public function testResolveIsResolved(
        ActionInterface $action,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider,
        ActionInterface $expectedAction
    ): void {
        $resolvedAssertion = $this->resolver->resolve($action, $pageProvider, $identifierProvider);

        $this->assertEquals($expectedAction, $resolvedAssertion);
    }

    /**
     * @return array[]
     */
    public function resolveIsResolvedDataProvider(): array
    {
        $actionParser = ActionParser::create();

        return [
            'interaction action with element reference identifier' => [
                'action' => $actionParser->parse('click $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('click $elements.element_name'),
                    '$".selector"'
                ),
            ],
            'interaction action with page element reference identifier' => [
                'action' => $actionParser->parse('click $page_import_name.elements.element_name'),
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
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('click $page_import_name.elements.element_name'),
                    '$".selector"'
                ),
            ],
            'input action with element reference identifier and literal value' => [
                'action' => $actionParser->parse('set $elements.element_name to "value"'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".selector"',
                ]),
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $elements.element_name to "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'input action with page element reference identifier and literal value' => [
                'action' => $actionParser->parse('set $page_import_name.elements.element_name to "value"'),
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
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $page_import_name.elements.element_name to "value"'),
                    '$".selector"',
                    '"value"'
                ),
            ],
            'input action with dom identifier and element reference value' => [
                'action' => $actionParser->parse('set $".selector" to $elements.element_name'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_name' => '$".resolved"',
                ]),
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $elements.element_name'),
                    '$".selector"',
                    '$".resolved"'
                ),
            ],
            'input action with dom identifier and page element reference value' => [
                'action' => $actionParser->parse('set $".selector" to $page_import_name.elements.element_name'),
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
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $page_import_name.elements.element_name'),
                    '$".selector"',
                    '$".resolved"'
                ),
            ],
            'input action with element reference identifier and element reference value' => [
                'action' => $actionParser->parse('set $elements.element_one to $elements.element_two'),
                'pageProvider' => new EmptyPageProvider(),
                'identifierProvider' => new IdentifierProvider([
                    'element_one' => '$".one"',
                    'element_two' => '$".two"',
                ]),
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $elements.element_one to $elements.element_two'),
                    '$".one"',
                    '$".two"'
                ),
            ],
            'input action with page element reference identifier and page element reference value' => [
                'action' => $actionParser->parse(
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
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse(
                        'set $page_import_name.elements.element_one to $page_import_name.elements.element_two'
                    ),
                    '$".one"',
                    '$".two"'
                ),
            ],
            'input action with dom identifier and imported page url value' => [
                'action' => $actionParser->parse('set $".selector" to $page_import_name.url'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com'),
                ]),
                'identifierProvider' => new EmptyIdentifierProvider(),
                'expectedAction' => new ResolvedAction(
                    $actionParser->parse('set $".selector" to $page_import_name.url'),
                    '$".selector"',
                    '"http://example.com"'
                ),
            ],
        ];
    }
}
