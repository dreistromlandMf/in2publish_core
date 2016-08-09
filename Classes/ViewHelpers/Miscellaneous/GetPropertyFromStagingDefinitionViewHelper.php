<?php
namespace In2code\In2publishCore\ViewHelpers\Miscellaneous;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetPropertyFromArrayViewHelper
 *
 * @package In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *            GNU Lesser General Public License, version 3 or later
 */
class GetPropertyFromStagingDefinitionViewHelper extends AbstractViewHelper
{
    /**
     * @var string
     */
    protected $emptyFieldValue = '---';

    /**
     * Get property of array
     *
     * @param \In2code\In2publishCore\Domain\Model\Record $record
     * @param string $propertyName
     * @param string $stagingLevel
     * @return string
     */
    public function render(Record $record, $propertyName, $stagingLevel = 'local')
    {
        $properties = ObjectAccess::getProperty($record, ucfirst($stagingLevel) . 'Properties');
        if (isset($properties[$propertyName])) {
            return $properties[$propertyName];
        }
        return $this->fallbackRootPageTitle($record, $propertyName, $stagingLevel);
    }

    /**
     * Return labels if PID 0 and tableName="pages"
     *
     * @param Record $record
     * @param string $propertyName
     * @param string $stagingLevel
     * @return string
     */
    protected function fallbackRootPageTitle(Record $record, $propertyName, $stagingLevel = 'local')
    {
        if ($record->getTableName() === 'pages' && $record->getIdentifier() === 0 && $propertyName === 'title') {
            if ($stagingLevel === 'local') {
                return LocalizationUtility::translate('label_stage', 'in2publish_core');
            } else {
                return LocalizationUtility::translate('label_production', 'in2publish_core');
            }
        }
        return $this->emptyFieldValue;
    }
}