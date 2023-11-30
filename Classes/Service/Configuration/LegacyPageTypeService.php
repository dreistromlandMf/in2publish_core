<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/*
 * Copyright notice
 *
 * (c) 2023 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function strpos;

class LegacyPageTypeService extends AbstractPageTypeService
{
    public function getTablesAllowedOnPage(int $pid, ?int $doktype): array
    {
        // The root page does not have a doktype. Just get all allowed tables.
        if (0 === $pid) {
            if (!isset($this->rtc[self::TYPE_ROOT])) {
                $this->rtc[self::TYPE_ROOT] = $this->getAllAllowedTableNames(self::TYPE_ROOT);
            }
            return $this->rtc[self::TYPE_ROOT];
        }

        $type = isset($GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) ? $doktype : 'default';
        $key = self::TYPE_PAGE . '_' . $type;

        if (!isset($this->rtc[$key])) {
            $allowedOnType = $this->getAllAllowedTableNames(self::TYPE_PAGE);
            $allowedOnDoktype = $GLOBALS['PAGES_TYPES'][$type]['allowedTables'];
            if (false === strpos($allowedOnDoktype, '*')) {
                foreach ($allowedOnType as $index => $table) {
                    if (!GeneralUtility::inList($allowedOnDoktype, $table)) {
                        unset($allowedOnType[$index]);
                    }
                }
            }

            $this->rtc[$key] = $allowedOnType;
        }
        return $this->rtc[$key];
    }
}
