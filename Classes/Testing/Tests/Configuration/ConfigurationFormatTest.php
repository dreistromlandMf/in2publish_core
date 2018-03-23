<?php
namespace In2code\In2publishCore\Testing\Tests\Configuration;

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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Config\ValidationContainer;
use In2code\In2publishCore\Testing\Data\ConfigDefinitionProvider;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationFormatTest
 */
class ConfigurationFormatTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run()
    {
        $container = GeneralUtility::makeInstance(ValidationContainer::class);
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $definition = $configContainer->getLocalDefinition();
        $actual = GeneralUtility::makeInstance(ConfigContainer::class)->get();
        $definition->validate($container, $actual);

        $errors = $container->getErrors();
        if (!empty($errors)) {
            return new TestResult('configuration.format_error', TestResult::ERROR, $errors);
        }

        return new TestResult('configuration.format_okay');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [];
    }
}
