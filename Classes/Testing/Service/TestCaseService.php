<?php
namespace In2code\In2publishCore\Testing\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;

/**
 * Class TestCaseService
 */
class TestCaseService
{
    /**
     * @return TestCaseInterface[]
     */
    public function getTests()
    {
        $tests = $this->getTestObjects();
        $orderedTests = array();

        while (count($tests) > 0) {
            $resolvedDependencies = 0;

            foreach ($tests as $testClass => $testObject) {
                if ($this->isDependencyMissing($testObject->getDependencies(), $orderedTests)) {
                    continue;
                }
                $orderedTests[$testClass] = $testObject;
                unset($tests[$testClass]);
                $resolvedDependencies++;
            }

            if (0 === $resolvedDependencies) {
                throw new \LogicException('Can not resolve testing dependencies', 1470066529);
            }
        }

        return $orderedTests;
    }

    /**
     * @param array $dependencies
     * @param array $orderedTests
     * @return bool
     */
    protected function isDependencyMissing(array $dependencies, array $orderedTests)
    {
        foreach ($dependencies as $dependency) {
            if (!isset($orderedTests[$dependency])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return TestCaseInterface[]
     */
    protected function getTestObjects()
    {
        $tests = array();
        foreach ($this->getTestClasses() as $class) {
            if (!is_subclass_of($class, 'In2code\\In2publishCore\\Testing\\Tests\\TestCaseInterface')) {
                throw new \InvalidArgumentException(
                    'The test class ' . $class . ' must implement the TestCaseInterface',
                    1470244507
                );
            }
            $tests[$class] = new $class();
        }
        return $tests;
    }

    /**
     * @return array
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function getTestClasses()
    {
        return $GLOBALS['in2publish_core']['tests'];
    }
}