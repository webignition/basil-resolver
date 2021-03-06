<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\Step\StepCollection;
use webignition\BasilModels\Step\StepInterface;
use webignition\BasilModels\Test\Test;
use webignition\BasilModels\Test\TestInterface;

class TestResolver
{
    public function __construct(
        private TestConfigurationResolver $configurationResolver,
        private StepResolver $stepResolver,
        private StepImportResolver $stepImportResolver
    ) {
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
     * @throws CircularStepImportException
     * @throws UnknownElementException
     * @throws UnknownItemException
     * @throws UnknownPageElementException
     */
    public function resolve(
        TestInterface $test,
        ProviderInterface $pageProvider,
        ProviderInterface $stepProvider,
        ProviderInterface $dataSetProvider
    ): TestInterface {
        $testName = $test->getPath();

        try {
            $configuration = $this->configurationResolver->resolve($test->getConfiguration(), $pageProvider);
        } catch (UnknownItemException $contextAwareException) {
            $contextAwareException->applyExceptionContext([
                ExceptionContextInterface::KEY_TEST_NAME => $testName,
            ]);

            throw $contextAwareException;
        }

        $resolvedSteps = [];
        foreach ($test->getSteps() as $stepName => $step) {
            if ($step instanceof StepInterface) {
                try {
                    $resolvedStep = $this->stepImportResolver->resolveStepImport($step, $stepProvider);
                    $resolvedStep = $this->stepImportResolver->resolveDataProviderImport(
                        $resolvedStep,
                        $dataSetProvider
                    );
                    $resolvedStep = $this->stepResolver->resolve($resolvedStep, $pageProvider);
                    $resolvedStep = $resolvedStep->withIdentifiers([]);

                    $resolvedSteps[$stepName] = $resolvedStep;
                } catch (
                    UnknownElementException |
                    UnknownItemException |
                    UnknownPageElementException $contextAwareException
                ) {
                    $contextAwareException->applyExceptionContext([
                        ExceptionContextInterface::KEY_TEST_NAME => $testName,
                        ExceptionContextInterface::KEY_STEP_NAME => $stepName,
                    ]);

                    throw $contextAwareException;
                }
            }
        }

        $resolvedTest = new Test($configuration, new StepCollection($resolvedSteps));
        $testPath = $test->getPath();

        if (null !== $testPath) {
            $resolvedTest = $resolvedTest->withPath($testPath);
        }

        return $resolvedTest;
    }
}
