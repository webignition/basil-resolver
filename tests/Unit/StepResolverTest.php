<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilContextAwareException\ContextAwareExceptionInterface;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModels\Action\InputAction;
use webignition\BasilModels\Action\InteractionAction;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilModels\Page\Page;
use webignition\BasilModels\Step\Step;
use webignition\BasilModels\Step\StepInterface;
use webignition\BasilParser\StepParser;
use webignition\BasilResolver\StepResolver;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;

class StepResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StepResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = StepResolver::createResolver();
    }

    /**
     * @dataProvider resolveForPendingImportResolutionStepDataProvider
     * @dataProvider resolveActionsAndAssertionsDataProvider
     * @dataProvider resolveIdentifierCollectionDataProvider
     */
    public function testResolveSuccess(
        StepInterface $step,
        PageProviderInterface $pageProvider,
        StepInterface $expectedStep
    ) {
        $resolvedStep = $this->resolver->resolve($step, $pageProvider);

        $this->assertEquals($expectedStep, $resolvedStep);
    }

    public function resolveForPendingImportResolutionStepDataProvider(): array
    {
        return [
            'pending import step: has step import name' => [
                'step' => $this->createStep([
                    'use' => 'import_name',
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step([], []))->withImportName('import_name'),
            ],
            'pending import step: has data provider import name' => [
                'step' => $this->createStep([
                    'data' => 'data_import_name',
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step([], []))->withDataImportName('data_import_name'),
            ],
        ];
    }

    public function resolveActionsAndAssertionsDataProvider(): array
    {
        $nonResolvableStep = $this->createStep([
            'actions' => [
                'wait 30',
            ],
            'assertions' => [
                '$".selector" exists',
            ],
        ]);

        return [
            'non-resolvable actions, non-resolvable assertions' => [
                'step' => $nonResolvableStep,
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => $nonResolvableStep
            ],
            'page element reference in action identifier' => [
                'step' => $this->createStep([
                    'actions' => [
                        'set $page_import_name.elements.examined to "value"',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'examined' => '$".examined"',
                        ]
                    )
                ]),
                'expectedStep' => new Step([
                    new InputAction(
                        'set $page_import_name.elements.examined to "value"',
                        '$page_import_name.elements.examined to "value"',
                        '$".examined"',
                        '"value"'
                    )
                ], []),
            ],
            'page element reference in action value' => [
                'step' => $this->createStep([
                    'actions' => [
                        'set $".examined" to $page_import_name.elements.expected',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'expected' => '$".expected"',
                        ]
                    )
                ]),
                'expectedStep' => new Step([
                    new InputAction(
                        'set $".examined" to $page_import_name.elements.expected',
                        '$".examined" to $page_import_name.elements.expected',
                        '$".examined"',
                        '$".expected"'
                    )
                ], []),
            ],
            'page element reference in assertion examined value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$page_import_name.elements.examined exists',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'examined' => '$".examined"',
                        ]
                    )
                ]),
                'expectedStep' => new Step([], [
                    new Assertion(
                        '$page_import_name.elements.examined exists',
                        '$".examined"',
                        'exists'
                    ),
                ]),
            ],
            'page element reference in assertion expected value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$".examined" is $page_import_name.elements.expected ',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'expected' => '$".expected"',
                        ]
                    )
                ]),
                'expectedStep' => new Step([], [
                    new ComparisonAssertion(
                        '$".examined" is $page_import_name.elements.expected',
                        '$".examined"',
                        'is',
                        '$".expected"'
                    ),
                ]),
            ],
            'element reference in action identifier' => [
                'step' => $this->createStep([
                    'actions' => [
                        'set $elements.examined to "value"',
                    ],
                    'elements' => [
                        'examined' => '$".examined"',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [
                        new InputAction(
                            'set $elements.examined to "value"',
                            '$elements.examined to "value"',
                            '$".examined"',
                            '"value"'
                        ),
                    ],
                    []
                ))->withIdentifiers([
                    'examined' => '$".examined"',
                ]),
            ],
            'element reference in action value' => [
                'step' => $this->createStep([
                    'actions' => [
                        'set $".examined" to $elements.expected',
                    ],
                    'elements' => [
                        'expected' => '$".expected"',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [
                        new InputAction(
                            'set $".examined" to $elements.expected',
                            '$".examined" to $elements.expected',
                            '$".examined"',
                            '$".expected"'
                        ),
                    ],
                    []
                ))->withIdentifiers([
                    'expected' => '$".expected"',
                ]),
            ],
            'attribute reference in action value' => [
                'step' => $this->createStep([
                    'actions' => [
                        'set $".examined" to $elements.expected.attribute_name',
                    ],
                    'elements' => [
                        'expected' => '$".expected"',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [
                        new InputAction(
                            'set $".examined" to $elements.expected.attribute_name',
                            '$".examined" to $elements.expected.attribute_name',
                            '$".examined"',
                            '$".expected".attribute_name'
                        )
                    ],
                    []
                ))->withIdentifiers([
                    'expected' => '$".expected"'
                ]),
            ],
            'element reference in assertion examined value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$elements.examined exists',
                    ],
                    'elements' => [
                        'examined' => '$".examined"'
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [],
                    [
                        new Assertion(
                            '$elements.examined exists',
                            '$".examined"',
                            'exists'
                        ),
                    ]
                ))->withIdentifiers([
                    'examined' => '$".examined"'
                ]),
            ],
            'element reference in assertion expected value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$".examined-selector" is $elements.expected',
                    ],
                    'elements' => [
                        'expected' => '$".expected"'
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [],
                    [
                        new ComparisonAssertion(
                            '$".examined-selector" is $elements.expected',
                            '$".examined-selector"',
                            'is',
                            '$".expected"'
                        ),
                    ]
                ))->withIdentifiers([
                    'expected' => '$".expected"'
                ]),
            ],
            'attribute reference in assertion examined value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$elements.examined.attribute_name exists',
                    ],
                    'elements' => [
                        'examined' => '$".examined"'
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [],
                    [
                        new Assertion(
                            '$elements.examined.attribute_name exists',
                            '$".examined".attribute_name',
                            'exists'
                        ),
                    ]
                ))->withIdentifiers([
                    'examined' => '$".examined"'
                ]),
            ],
            'attribute reference in assertion expected value' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$".examined" is $elements.expected.attribute_name',
                    ],
                    'elements' => [
                        'expected' => '$".expected"'
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step(
                    [],
                    [
                        new ComparisonAssertion(
                            '$".examined" is $elements.expected.attribute_name',
                            '$".examined"',
                            'is',
                            '$".expected".attribute_name'
                        ),
                    ]
                ))->withIdentifiers([
                    'expected' => '$".expected"'
                ]),
            ],
        ];
    }

    public function resolveIdentifierCollectionDataProvider(): array
    {
        return [
            'no resolvable element identifiers' => [
                'step' => $this->createStep([
                    'elements' => [
                        'name' => '$".selector"',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedStep' => (new Step([], []))
                    ->withIdentifiers([
                        'name' => '$".selector"',
                    ]),
            ],
            'identifier with page element references, unused by actions or assertions' => [
                'step' => $this->createStep([
                    'elements' => [
                        'step_element_name' => '$page_import_name.elements.page_element_name',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'page_element_name' => '$".resolved"',
                        ]
                    )
                ]),
                'expectedStep' => (new Step([], []))
                    ->withIdentifiers([
                        'step_element_name' => '$".resolved"',
                    ]),
            ],
            'identifier with page element references, used by actions and assertions' => [
                'step' => $this->createStep([
                    'actions' => [
                        'click $page_import_name.elements.page_element_name',
                    ],
                    'assertions' => [
                        '$page_import_name.elements.page_element_name exists',
                    ],
                    'elements' => [
                        'step_element_name' => '$page_import_name.elements.page_element_name',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com/',
                        [
                            'page_element_name' => '$".resolved"',
                        ]
                    )
                ]),
                'expectedStep' => (new Step(
                    [
                        new InteractionAction(
                            'click $page_import_name.elements.page_element_name',
                            'click',
                            '$page_import_name.elements.page_element_name',
                            '$".resolved"'
                        ),
                    ],
                    [
                        new Assertion(
                            '$page_import_name.elements.page_element_name exists',
                            '$".resolved"',
                            'exists'
                        ),
                    ]
                ))
                    ->withIdentifiers([])
                    ->withIdentifiers([
                        'step_element_name' => '$".resolved"',
                    ])
            ],
        ];
    }

    /**
     * @dataProvider resolvePageElementReferencesThrowsExceptionDataProvider
     */
    public function testResolvePageElementReferencesThrowsException(
        StepInterface $step,
        PageProviderInterface $pageProvider,
        ContextAwareExceptionInterface $expectedException
    ) {
        try {
            $this->resolver->resolve($step, $pageProvider);

            $this->fail('Exception not thrown');
        } catch (ContextAwareExceptionInterface $contextAwareException) {
            $this->assertEquals($expectedException, $contextAwareException);
        }
    }

    public function resolvePageElementReferencesThrowsExceptionDataProvider(): array
    {
        return [
            'UnknownPageElementException: action has page element reference, referenced page lacks element' => [
                'step' => $this->createStep([
                    'actions' => [
                        'click $page_import_name.elements.element_name',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com/'),
                ]),
                'expectedException' => $this->applyContentToException(
                    new UnknownPageElementException('page_import_name', 'element_name'),
                    'click $page_import_name.elements.element_name'
                ),
            ],
            'UnknownPageElementException: assertion has page element reference, referenced page lacks element' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$page_import_name.elements.element_name exists',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com/'),
                ]),
                'expectedException' => $this->applyContentToException(
                    new UnknownPageElementException('page_import_name', 'element_name'),
                    '$page_import_name.elements.element_name exists'
                ),
            ],
            'UnknownPageException: action has page element reference, page does not exist' => [
                'step' => $this->createStep([
                    'actions' => [
                        'click $page_import_name.elements.element_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedException' => $this->applyContentToException(
                    new UnknownPageException('page_import_name'),
                    'click $page_import_name.elements.element_name'
                ),
            ],
            'UnknownPageException: assertion has page element reference, page does not exist' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$page_import_name.elements.element_name exists',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedException' => $this->applyContentToException(
                    new UnknownPageException('page_import_name'),
                    '$page_import_name.elements.element_name exists'
                ),
            ],
            'UnknownElementException: action has element reference, element missing' => [
                'step' => $this->createStep([
                    'actions' => [
                        'click $elements.element_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedException' => $this->applyContentToException(
                    new UnknownElementException('element_name'),
                    'click $elements.element_name'
                ),
            ],
            'UnknownElementException: assertion has page element reference, referenced page invalid' => [
                'step' => $this->createStep([
                    'assertions' => [
                        '$elements.element_name exists',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'expectedException' => $this->applyContentToException(
                    new UnknownElementException('element_name'),
                    '$elements.element_name exists'
                ),
            ],
        ];
    }

    private function createStep(array $stepData): StepInterface
    {
        return (StepParser::create())->parse($stepData);
    }

    private function applyContentToException(
        ContextAwareExceptionInterface $contextAwareException,
        string $content
    ): ContextAwareExceptionInterface {
        $contextAwareException->applyExceptionContext([
            ExceptionContextInterface::KEY_CONTENT => $content,
        ]);

        return $contextAwareException;
    }
}