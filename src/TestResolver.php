<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
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
     * @param ProviderInterface $pageProvider
     * @param ProviderInterface $stepProvider
     * @param ProviderInterface $dataSetProvider
     *
     * @return TestInterface
     *
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
            try {
                $resolvedStep = $this->stepImportResolver->resolveStepImport($step, $stepProvider);
                $resolvedStep = $this->stepImportResolver->resolveDataProviderImport($resolvedStep, $dataSetProvider);
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

        $resolvedTest = new Test($configuration, $resolvedSteps);
        $testPath = $test->getPath();

        if (null !== $testPath) {
            $resolvedTest = $resolvedTest->withPath($testPath);
        }

        return $resolvedTest;
    }
}
