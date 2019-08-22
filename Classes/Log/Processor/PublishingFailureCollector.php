<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Log\Processor;

/*
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
 */

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Processor\AbstractProcessor;
use TYPO3\CMS\Core\SingletonInterface;
use function min;

/**
 * Class PublishingErrorProcessor
 */
class PublishingFailureCollector extends AbstractProcessor implements SingletonInterface
{
    /**
     * @var array
     */
    protected $failures = [];

    /**
     * @var int
     */
    protected $highestSeverity = LogLevel::DEBUG;

    /**
     * @param LogRecord $logRecord
     *
     * @return LogRecord|void
     */
    public function processLogRecord(LogRecord $logRecord)
    {
        $this->highestSeverity = min($this->highestSeverity, $logRecord->getLevel());
        if ($logRecord->getLevel() <= LogLevel::WARNING) {
            $this->failures[$logRecord->getMessage()][] = $logRecord;
        }
        return $logRecord;
    }

    /**
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * @return int
     */
    public function getMostCriticalLogLevel(): int
    {
        return $this->highestSeverity;
    }
}