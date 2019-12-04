<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilContextAwareException\ContextAwareExceptionInterface;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\DataSet\DataSetProvider;
use webignition\BasilModelProvider\DataSet\DataSetProviderInterface;
use webignition\BasilModelProvider\DataSet\EmptyDataSetProvider;
use webignition\BasilModelProvider\Exception\UnknownDataProviderException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Exception\UnknownStepException;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModelProvider\Step\EmptyStepProvider;
use webignition\BasilModelProvider\Step\StepProvider;
use webignition\BasilModelProvider\Step\StepProviderInterface;
use webignition\BasilModels\Action\InputAction;
use webignition\BasilModels\Action\InteractionAction;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\ComparisonAssertion;
use webignition\BasilModels\DataSet\DataSetCollection;
use webignition\BasilModels\Page\Page;
use webignition\BasilModels\Step\Step;
use webignition\BasilModels\Test\Configuration;
use webignition\BasilModels\Test\Test;
use webignition\BasilModels\Test\TestInterface;
use webignition\BasilParser\StepParser;
use webignition\BasilParser\Test\TestParser;
use webignition\BasilResolver\TestResolver;
use webignition\BasilResolver\UnknownElementException;
use webignition\BasilResolver\UnknownPageElementException;

class TestResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = TestResolver::createResolver();
    }

    /**
     * @dataProvider resolveSuccessDataProvider
     */
    public function testResolveSuccess(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        TestInterface $expectedTest
    ) {
        $resolvedTest = $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);

        $this->assertEquals($expectedTest, $resolvedTest);
    }

    public function resolveSuccessDataProvider(): array
    {
//        $actionFactory = ActionFactory::createFactory();
//        $assertionFactory = AssertionFactory::createFactory();
//
//        $actionSelectorIdentifier = new DomIdentifier('.action-selector');
//        $assertionSelectorIdentifier = new DomIdentifier('.assertion-selector');
//
//        '$".action-selector"' = TestIdentifierFactory::createElementIdentifier(
//            '.action-selector',
//            1,
//            'action_selector'
//        );
//
//        '$".assertion-selector"' = TestIdentifierFactory::createElementIdentifier(
//            '.assertion-selector',
//            1,
//            'assertion_selector'
//        );
//
//        $pageElementReferenceActionIdentifier = TestIdentifierFactory::createPageElementReferenceIdentifier(
//            new PageElementReference(
//                'page_import_name.elements.action_selector',
//                'page_import_name',
//                'action_selector'
//            ),
//            'action_selector'
//        );
//
//        $pageElementReferenceAssertionIdentifier = TestIdentifierFactory::createPageElementReferenceIdentifier(
//            new PageElementReference(
//                'page_import_name.elements.assertion_selector',
//                'page_import_name',
//                'assertion_selector'
//            ),
//            'assertion_selector'
//        );
//
        $expectedResolvedDataTest = new Test('test name', new Configuration('', ''), [
            'step name' => (new Step(
                [
                    new InputAction(
                        'set $".action-selector" to $data.key1',
                        '$".action-selector" to $data.key1',
                        '$".action-selector"',
                        '$data.key1'
                    )
                ],
                [
                    new ComparisonAssertion(
                        '$".assertion-selector" is $data.key2',
                        '$".assertion-selector"',
                        'is',
                        '$data.key2'
                    )
                ]
            ))->withData(new DataSetCollection([
                '0' => [
                    'key1' => 'key1value1',
                    'key2' => 'key2value1',
                ],
                '1' => [
                    'key1' => 'key1value2',
                    'key2' => 'key2value2',
                ],
            ])),
        ]);

        $testParser = TestParser::create();
        $stepParser = StepParser::create();

        return [
            'empty test' => [
                'test' => $testParser->parse('', 'test name', []),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), []),
            ],
            'configuration is resolved' => [
                'test' => $testParser->parse('', 'test name', [
                    'config' => [
                        'url' => 'page_import_name.url',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com/'),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(
                    'test name',
                    new Configuration('', 'http://example.com/'),
                    []
                ),
            ],
            'empty step' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step([], []),
                ]),
            ],
            'no imports, actions and assertions require no resolution' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'actions' => [
                            'click $".action-selector"',
                        ],
                        'assertions' => [
                            '$".assertion-selector" exists',
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $".action-selector"',
                                'click',
                                '$".action-selector"',
                                '$".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '$".assertion-selector" exists',
                                '$".assertion-selector"',
                                'exists'
                            )
                        ]
                    ),
                ]),
            ],
            'actions and assertions require resolution of page imports' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'actions' => [
                            'click $page_import_name.elements.action_selector',
                        ],
                        'assertions' => [
                            '$page_import_name.elements.assertion_selector exists',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'action_selector' => '$".action-selector"',
                            'assertion_selector' => '$".assertion-selector"',
                        ]
                    ),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $page_import_name.elements.action_selector',
                                'click',
                                '$page_import_name.elements.action_selector',
                                '$".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '$page_import_name.elements.assertion_selector exists',
                                '$".assertion-selector"',
                                'exists'
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions require no resolution' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'actions' => [
                            'click $".action-selector"',
                        ],
                        'assertions' => [
                            '$".assertion-selector" exists',
                        ],
                    ]),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $".action-selector"',
                                'click',
                                '$".action-selector"',
                                '$".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '$".assertion-selector" exists',
                                '$".assertion-selector"',
                                'exists'
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions require element resolution' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'elements' => [
                            'elements_action_selector' => '$page_import_name.elements.page_action_selector',
                            'elements_assertion_selector' => '$page_import_name.elements.page_assertion_selector',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'page_action_selector' => '$".action-selector"',
                            'page_assertion_selector' => '$".assertion-selector"',
                        ]
                    ),
                ]),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'actions' => [
                            'click $elements.elements_action_selector'
                        ],
                        'assertions' => [
                            '$elements.elements_assertion_selector exists'
                        ],
                    ]),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $elements.elements_action_selector',
                                'click',
                                '$elements.elements_action_selector',
                                '$".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '$elements.elements_assertion_selector exists',
                                '$".assertion-selector"',
                                'exists'
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions use inline data' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'data' => [
                            '0' => [
                                'key1' => 'key1value1',
                                'key2' => 'key2value1',
                            ],
                            '1' => [
                                'key1' => 'key1value2',
                                'key2' => 'key2value2',
                            ],
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'actions' => [
                            'set $".action-selector" to $data.key1'
                        ],
                        'assertions' => [
                            '$".assertion-selector" is $data.key2'
                        ],
                    ]),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => $expectedResolvedDataTest,
            ],
            'empty step imports step, imported actions and assertions use imported data' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'data' => 'data_provider_import_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'actions' => [
                            'set $".action-selector" to $data.key1'
                        ],
                        'assertions' => [
                            '$".assertion-selector" is $data.key2'
                        ],
                    ]),
                ]),
                'dataSetProvider' => new DataSetProvider([
                    'data_provider_import_name' => new DataSetCollection([
                        '0' => [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ],
                        '1' => [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ],
                    ]),
                ]),
                'expectedTest' => $expectedResolvedDataTest,
            ],
            'deferred step import, imported actions and assertions require element resolution' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'elements' => [
                            'action_selector' => '$page_import_name.elements.action_selector',
                            'assertion_selector' => '$page_import_name.elements.assertion_selector',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'action_selector' => '$".action-selector"',
                            'assertion_selector' => '$".assertion-selector"',
                        ]
                    ),
                ]),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'use' => 'deferred',
                    ]),
                    'deferred' => $stepParser->parse([
                        'actions' => [
                            'click $elements.action_selector',
                        ],
                        'assertions' => [
                            '$elements.assertion_selector exists',
                        ],
                    ]),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $elements.action_selector',
                                'click',
                                '$elements.action_selector',
                                '$".action-selector"'
                            ),
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector exists',
                                '$".assertion-selector"',
                                'exists'
                            ),
                        ]
                    ),
                ]),
            ],
            'deferred step import, imported actions and assertions use imported data' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'data' => 'data_provider_import_name',
                        'elements' => [
                            'action_selector' => '$page_import_name.elements.action_selector',
                            'assertion_selector' => '$page_import_name.elements.assertion_selector',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        'http://example.com',
                        [
                            'action_selector' => '$".action-selector"',
                            'assertion_selector' => '$".assertion-selector"',
                        ]
                    ),
                ]),
                'stepProvider' => new StepProvider([
                    'step_import_name' => $stepParser->parse([
                        'use' => 'deferred',
                    ]),
                    'deferred' => $stepParser->parse([
                        'actions' => [
                            'set $elements.action_selector to $data.key1',
                        ],
                        'assertions' => [
                            '$elements.assertion_selector is $data.key2',
                        ],
                    ]),
                ]),
                'dataSetProvider' => new DataSetProvider([
                    'data_provider_import_name' => new DataSetCollection([
                        '0' => [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ],
                        '1' => [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ],
                    ]),
                ]),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => (new Step(
                        [
                            new InputAction(
                                'set $elements.action_selector to $data.key1',
                                '$elements.action_selector to $data.key1',
                                '$".action-selector"',
                                '$data.key1'
                            )
                        ],
                        [
                            new ComparisonAssertion(
                                '$elements.assertion_selector is $data.key2',
                                '$".assertion-selector"',
                                'is',
                                '$data.key2'
                            )
                        ]
                    ))->withData(new DataSetCollection([
                        '0' => [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ],
                        '1' => [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ],
                    ])),
                ]),
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsExceptionDataProvider
     */
    public function testResolveThrowsException(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        ContextAwareExceptionInterface $expectedException
    ) {
        try {
            $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);

            $this->fail('Exception not thrown');
        } catch (ContextAwareExceptionInterface $contextAwareException) {
            $this->assertEquals($expectedException, $contextAwareException);
        }
    }

    public function resolveThrowsExceptionDataProvider(): array
    {
        $testParser = TestParser::create();

        return [
            'UnknownDataProviderException: test.data references a data provider that has not been defined' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                        'data' => 'data_provider_import_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new StepProvider([
                    'step_import_name' => new Step([], []),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownDataProviderException('data_provider_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownPageException: config.url references page not defined within a collection' => [
                'test' => $testParser->parse('', 'test name', [
                    'config' => [
                        'url' => 'page_import_name.url',
                    ],

                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageException('page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ]
                ),
            ],
            'UnknownPageException: assertion string references page not defined within a collection' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'assertions' => [
                            '$page_import_name.elements.element_name exists'
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageException('page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$page_import_name.elements.element_name exists',
                    ]
                ),
            ],
            'UnknownPageException: action string references page not defined within a collection' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'actions' => [
                            'click $page_import_name.elements.element_name'
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageException('page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $page_import_name.elements.element_name',
                    ]
                ),
            ],
            'UnknownPageElementException: test.elements references element that does not exist within a page' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'elements' => [
                            'non_existent' => '$page_import_name.elements.non_existent',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownPageElementException: assertion string references element that does not exist within a page' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'assertions' => [
                            '$page_import_name.elements.non_existent exists',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$page_import_name.elements.non_existent exists',
                    ]
                ),
            ],
            'UnknownPageElementException: action string references element that does not exist within a page' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'actions' => [
                            'click $page_import_name.elements.non_existent',
                        ],
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $page_import_name.elements.non_existent',
                    ]
                ),
            ],
            'UnknownStepException: step.use references step not defined within a collection' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'use' => 'step_import_name',
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownStepException('step_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownElementException: action element parameter references unknown step element' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'actions' => [
                            'click $elements.element_name',
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownElementException('element_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $elements.element_name',
                    ]
                ),
            ],
            'UnknownElementException: assertion element parameter references unknown step element' => [
                'test' => $testParser->parse('', 'test name', [
                    'step name' => [
                        'assertions' => [
                            '$elements.element_name exists',
                        ],
                    ],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownElementException('element_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$elements.element_name exists',
                    ]
                )
            ],
        ];
    }

    private function applyContextToException(
        ContextAwareExceptionInterface $contextAwareException,
        array $context
    ): ContextAwareExceptionInterface {
        $contextAwareException->applyExceptionContext($context);

        return $contextAwareException;
    }
}
