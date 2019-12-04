<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\DataSet\DataSetProviderInterface;
use webignition\BasilModelProvider\Exception\UnknownDataProviderException;
use webignition\BasilModelProvider\Exception\UnknownPageException;
use webignition\BasilModelProvider\Exception\UnknownStepException;
use webignition\BasilModelProvider\Page\PageProviderInterface;
use webignition\BasilModelProvider\Step\StepProviderInterface;
use webignition\BasilModels\Test\Test;
use webignition\BasilModels\Test\TestInterface;

class TestResolver
{
    private $configurationResolver;
    private $stepResolver;
    private $stepImportResolver;

    public function __construct(
        TestConfigurationResolver $configurationResolver,
        StepResolver $stepResolver,
        StepImportResolver $stepImportResolver
    ) {
        $this->configurationResolver = $configurationResolver;
        $this->stepResolver = $stepResolver;
        $this->stepImportResolver = $stepImportResolver;
    }

    public static function createResolver(): TestResolver
    {
        return new TestResolver(
            TestConfigurationResolver::createResolver(),
            StepResolver::createResolver(),
            StepImportResolver::createResolver()
        );
    }

    /**
     * @param TestInterface $test
     * @param PageProviderInterface $pageProvider
     * @param StepProviderInterface $stepProvider
     * @param DataSetProviderInterface $dataSetProvider
     *
     * @return TestInterface
     *
     * @throws CircularStepImportException
     * @throws UnknownDataProviderException
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownStepException
     */
    public function resolve(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider
    ): TestInterface {
        $testName = $test->getPath();

        try {
            $configuration = $this->configurationResolver->resolve($test->getConfiguration(), $pageProvider);
        } catch (UnknownPageException $contextAwareException) {
            $contextAwareException->applyExceptionContext([
                ExceptionContextInterface::KEY_TEST_NAME => $testName,
            ]);

            throw $contextAwareException;
        }

        $resolvedSteps = [];
        foreach ($test->getSteps() as $stepName => $step) {
            try {
                $resolvedStep = $this->stepImportResolver->resolveStepImport($step, $stepProvider);
                $resolvedStep = $this->stepImportResolver->resolveDataProviderImport($resolvedStep, $dataSetProvider);
                $resolvedStep = $this->stepResolver->resolve($resolvedStep, $pageProvider);
                $resolvedStep = $resolvedStep->withIdentifiers([]);

                $resolvedSteps[$stepName] = $resolvedStep;
            } catch (
                UnknownDataProviderException |
                UnknownElementException |
                UnknownPageElementException |
                UnknownPageException |
                UnknownStepException $contextAwareException
            ) {
                $contextAwareException->applyExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => $testName,
                    ExceptionContextInterface::KEY_STEP_NAME => $stepName,
                ]);

                throw $contextAwareException;
            }
        }

        return new Test($testName, $configuration, $resolvedSteps);
    }
}
