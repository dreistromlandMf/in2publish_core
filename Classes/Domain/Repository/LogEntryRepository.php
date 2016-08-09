<?php
namespace In2code\In2publishCore\Domain\Repository;

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

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class LogEntryRepository
 *
 * @package In2code\In2publish\Domain\Repository
 */
class LogEntryRepository
{
    const GREATER_THAN = ' > %d';
    const LOWER_THAN = ' < %d';
    const GREATER_THAN_OR_EQUAL = ' >= %d';
    const LOWER_THAN_OR_EQUAL = ' <= %d';
    const EQUALS = ' = %d';
    const STRICT_LIKE = ' LIKE "%s"';
    const RANGE_LIKE = ' LIKE %%%s%%';

    /**
     * @var array
     */
    protected $allowedOperators = array(
        self::GREATER_THAN,
        self::LOWER_THAN,
        self::GREATER_THAN_OR_EQUAL,
        self::LOWER_THAN_OR_EQUAL,
        self::EQUALS,
        self::STRICT_LIKE,
        self::RANGE_LIKE,
    );

    /**
     * @var string
     */
    protected $tableName = 'tx_in2code_in2publish_log';

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * @var int
     */
    protected $limit = 250;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var array
     */
    protected $filter = array(
        'component LIKE "In2code.In2publishCore.%"',
    );

    /**
     * @var array
     */
    protected $propertyOperatorMap = array(
        'level' => self::LOWER_THAN_OR_EQUAL,
    );

    /**
     * @return array
     */
    public function getPropertyNames()
    {
        return array_keys($this->propertyOperatorMap);
    }

    /**
     * Adds a filter to apply at search queries
     *
     * @param string $propertyName
     * @param string $propertyValue
     * @return void
     * @throws \Exception
     */
    public function setFilter($propertyName, $propertyValue)
    {
        if (!array_key_exists($propertyName, $this->propertyOperatorMap)) {
            throw new \Exception(
                'The propertyName "' . htmlspecialchars($propertyName) . '" is not allowed',
                1425551782
            );
        }
        $operator = $this->propertyOperatorMap[$propertyName];
        if (in_array($operator, array(self::STRICT_LIKE, self::RANGE_LIKE))) {
            $propertyValue = $this->databaseConnection->escapeStrForLike($propertyValue, $this->tableName);
        }
        $this->filter[] = $propertyName . sprintf($operator, $propertyValue);
    }

    /**
     * Returns a list of all logged levels
     *
     * @return array
     */
    public function getLogLevels()
    {
        $results = $this->databaseConnection->exec_SELECTgetRows('level', $this->tableName, '1=1', 'level');
        $logLevels = array();
        foreach ($results as $result) {
            $logLevels[$result['level']] = $result['level'];
        }
        return $logLevels;
    }

    /**
     * @return LogEntryRepository
     */
    public function __construct()
    {
        $this->databaseConnection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    /**
     * Count all existing log entries
     *
     * @return int
     */
    public function countAll()
    {
        return (int)$this->databaseConnection->exec_SELECTcountRows('uid', $this->tableName);
    }

    /**
     * @return int
     */
    public function countFiltered()
    {
        return (int)$this->databaseConnection->exec_SELECTcountRows(
            'uid',
            $this->tableName,
            implode(' AND ', $this->filter)
        );
    }

    /**
     * Fetch filtered logs from the database
     *
     * @return array
     */
    public function getFiltered()
    {
        $implodedFilter = implode(' AND ', $this->filter);
        $logEntries = (array)$this->databaseConnection->exec_SELECTgetRows(
            '*',
            $this->tableName,
            $implodedFilter,
            '',
            'uid DESC',
            $this->offset . ',' . $this->limit
        );
        foreach ($logEntries as &$logEntry) {
            if (strpos($logEntry['data'], '- ') === 0) {
                $logEntry['data'] = substr($logEntry['data'], 2);
            }
            $logEntry['timestamp'] = substr($logEntry['time_micro'], 0, strpos($logEntry['time_micro'], '.'));
        }
        return $logEntries;
    }

    /**
     * Maximum Logs to fetch
     *
     * @param int $limit
     * @return void
     */
    public function setLimit($limit)
    {
        $this->limit = (int)$limit;
    }

    /**
     * Skip the first x Logs
     *
     * @param int $offset
     * @return void
     */
    public function setOffset($offset)
    {
        $this->offset = (int)$offset;
    }

    /**
     * Delete all Logs from the Database
     *
     * @return void
     */
    public function flush()
    {
        $this->databaseConnection->exec_DELETEquery($this->tableName, 'component LIKE "In2code.In2publishCore.%"');
    }

    /**
     * Need for tests
     *
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }
}