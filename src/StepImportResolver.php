<?php

declare(strict_types=1);

namespace webignition\BasilResolver;

use webignition\BasilModelProvider\DataSet\DataSetProviderInterface;
use webignition\BasilModelProvider\Exception\UnknownDataProviderException;
use webignition\BasilModelProvider\Exception\UnknownStepException;
use webignition\BasilModelProvider\Step\StepProviderInterface;
use webignition\BasilModels\Step\StepInterface;

class StepImportResolver
{
    public static function createResolver(): StepImportResolver
    {
        return new StepImportResolver();
    }

    /**
     * @param StepInterface $step
     * @param StepProviderInterface $stepProvider
     * @param string[] $handledImportNames
     *
     * @return StepInterface
     *
     * @throws CircularStepImportException
     * @throws UnknownStepException
     */
    public function resolveStepImport(
        StepInterface $step,
        StepProviderInterface $stepProvider,
        array $handledImportNames = []
    ): StepInterface {
        $importName = $step->getImportName();

        if (null !== $importName) {
            if (in_array($importName, $handledImportNames)) {
                throw new CircularStepImportException($importName);
            }

            $parentStep = $stepProvider->findStep($importName);

            if ($parentStep->requiresImportResolution()) {
                $handledImportNames[] = $importName;
                $parentStep = $this->resolveStepImport($parentStep, $stepProvider, $handledImportNames);
            }

            $step = $step
                ->withPrependedActions($parentStep->getActions())
                ->withPrependedAssertions($parentStep->getAssertions());

            $step = $step->removeImportName();
        }

        return $step;
    }

    /**
     * @param StepInterface $step
     * @param DataSetProviderInterface $dataSetProvider
     *
     * @return StepInterface
     *
     * @throws UnknownDataProviderException
     */
    public function resolveDataProviderImport(
        StepInterface $step,
        DataSetProviderInterface $dataSetProvider
    ): StepInterface {
        $dataProviderImportName = $step->getDataImportName();

        if (null !== $dataProviderImportName) {
            $step = $step->withData($dataSetProvider->findDataSetCollection($dataProviderImportName));

            $step = $step->removeDataImportName();
        }

        return $step;
    }
}
