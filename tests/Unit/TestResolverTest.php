<?php

declare(strict_types=1);

namespace webignition\BasilResolver\Tests\Unit;

use webignition\BasilContextAwareException\ContextAwareExceptionInterface;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\DataSet\DataSetProvider;
use webignition\BasilModelProvider\DataSet\EmptyDataSetProvider;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\Page\EmptyPageProvider;
use webignition\BasilModelProvider\Page\PageProvider;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModelProvider\Step\EmptyStepProvider;
use webignition\BasilModelProvider\Step\StepProvider;
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
        ProviderInterface $pageProvider,
        ProviderInterface $stepProvider,
        ProviderInterface $dataSetProvider,
        TestInterface $expectedTest
    ) {
        $resolvedTest = $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);

        $this->assertEquals($expectedTest, $resolvedTest);
    }

    public function resolveSuccessDataProvider(): array
    {
        $expectedResolvedDataTest = new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(new Configuration('', ''), []),
            ],
            'empty test with path' => [
                'test' => $testParser->parse([])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => (new Test(new Configuration('', ''), []))->withPath('test.yml'),
            ],
            'configuration is resolved' => [
                'test' => $testParser->parse([
                    'config' => [
                        'url' => 'page_import_name.url',
                    ],
                ]),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com/'),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(
                    new Configuration('', 'http://example.com/'),
                    []
                ),
            ],
            'empty step' => [
                'test' => $testParser->parse([
                    'step name' => [],
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(new Configuration('', ''), [
                    'step name' => new Step([], []),
                ]),
            ],
            'no imports, actions and assertions require no resolution' => [
                'test' => $testParser->parse([
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
                'expectedTest' => new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([
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
                        'page_import_name',
                        'http://example.com',
                        [
                            'action_selector' => '$".action-selector"',
                            'assertion_selector' => '$".assertion-selector"',
                        ]
                    ),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([
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
                'expectedTest' => new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([
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
                        'page_import_name',
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
                'expectedTest' => new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([
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
                'test' => $testParser->parse([
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
                'test' => $testParser->parse([
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
                        'page_import_name',
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
                'expectedTest' => new Test(new Configuration('', ''), [
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
                'test' => $testParser->parse([
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
                        'page_import_name',
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
                'expectedTest' => new Test(new Configuration('', ''), [
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
        ProviderInterface $pageProvider,
        ProviderInterface $stepProvider,
        ProviderInterface $dataSetProvider,
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
                'test' => $testParser->parse([
                    'step name' => [
                        'use' => 'step_import_name',
                        'data' => 'data_provider_import_name',
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new StepProvider([
                    'step_import_name' => new Step([], []),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownItemException(UnknownItemException::TYPE_DATASET, 'data_provider_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownPageException: config.url references page not defined within a collection' => [
                'test' => $testParser->parse([
                    'config' => [
                        'url' => 'page_import_name.url',
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownItemException(UnknownItemException::TYPE_PAGE, 'page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                    ]
                ),
            ],
            'UnknownPageException: assertion string references page not defined within a collection' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'assertions' => [
                            '$page_import_name.elements.element_name exists'
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownItemException(UnknownItemException::TYPE_PAGE, 'page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$page_import_name.elements.element_name exists',
                    ]
                ),
            ],
            'UnknownPageException: action string references page not defined within a collection' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'actions' => [
                            'click $page_import_name.elements.element_name'
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownItemException(UnknownItemException::TYPE_PAGE, 'page_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $page_import_name.elements.element_name',
                    ]
                ),
            ],
            'UnknownPageElementException: test.elements references element that does not exist within a page' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'elements' => [
                            'non_existent' => '$page_import_name.elements.non_existent',
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownPageElementException: assertion string references element that does not exist within a page' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'assertions' => [
                            '$page_import_name.elements.non_existent exists',
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$page_import_name.elements.non_existent exists',
                    ]
                ),
            ],
            'UnknownPageElementException: action string references element that does not exist within a page' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'actions' => [
                            'click $page_import_name.elements.non_existent',
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page('page_import_name', 'http://example.com')
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownPageElementException('page_import_name', 'non_existent'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $page_import_name.elements.non_existent',
                    ]
                ),
            ],
            'UnknownStepException: step.use references step not defined within a collection' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'use' => 'step_import_name',
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownItemException(UnknownItemException::TYPE_STEP, 'step_import_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ]
                ),
            ],
            'UnknownElementException: action element parameter references unknown step element' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'actions' => [
                            'click $elements.element_name',
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownElementException('element_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => 'click $elements.element_name',
                    ]
                ),
            ],
            'UnknownElementException: assertion element parameter references unknown step element' => [
                'test' => $testParser->parse([
                    'step name' => [
                        'assertions' => [
                            '$elements.element_name exists',
                        ],
                    ],
                ])->withPath('test.yml'),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => $this->applyContextToException(
                    new UnknownElementException('element_name'),
                    [
                        ExceptionContextInterface::KEY_TEST_NAME => 'test.yml',
                        ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                        ExceptionContextInterface::KEY_CONTENT => '$elements.element_name exists',
                    ]
                )
            ],
        ];
    }

    /**
     * @param ContextAwareExceptionInterface $contextAwareException
     *
     * @param array<string, string> $context
     *
     * @return ContextAwareExceptionInterface
     */
    private function applyContextToException(
        ContextAwareExceptionInterface $contextAwareException,
        array $context
    ): ContextAwareExceptionInterface {
        $contextAwareException->applyExceptionContext($context);

        return $contextAwareException;
    }
}
