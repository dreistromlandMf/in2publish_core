<?php
namespace In2code\In2publishCore\ViewHelpers\Record;

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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * GetMergedPropertyViewHelper
 *
 * @package In2code.In2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *            GNU Lesser General Public License, version 3 or later
 */
class GetMergedPropertyViewHelper extends AbstractViewHelper
{
    /**
     * Call getMergedProperty on a record
     *
     * @param Record $record
     * @param string $propertyName
     * @return mixed
     */
    public function render(Record $record, $propertyName)
    {
        return $record->getMergedProperty($propertyName);
    }
}