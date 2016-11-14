<?php
namespace In2code\In2publishCore\Domain\PostProcessing;

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

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\PropertyReflection;

/**
 * Class FileIndexPostProcessor
 */
class FileIndexPostProcessor implements SingletonInterface
{
    /**
     * @var RecordInterface[]
     */
    protected $registeredInstances = array();

    /**
     * @param RecordFactory $recordFactory
     * @param RecordInterface $instance
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function registerInstance(RecordFactory $recordFactory, RecordInterface $instance)
    {
        if ('sys_file' === $instance->getTableName()) {
            $this->registeredInstances[] = $instance;
        }
    }

    /**
     * @param RecordFactory $recordFactory
     * @param RecordInterface $instance
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postProcess(RecordFactory $recordFactory, RecordInterface $instance)
    {
        $resourceFactory = ResourceFactory::getInstance();
        /** @var RecordInterface[][] $sortedRecords */
        $sortedRecords = array();
        $storages = array();
        foreach ($this->registeredInstances as $record) {
            if (null === $uid = $record->getLocalProperty('storage')) {
                $uid = $record->getForeignProperty('storage');
            }
            if (!isset($storages[$uid])) {
                $storages[$uid] = $resourceFactory->getStorageObject($uid);
            }
            $sortedRecords[$uid][] = $record;
        }
        $this->registeredInstances = array();

        $this->prefetchForeignInformationFiles($storages, $sortedRecords);

        foreach ($sortedRecords as $storageIndex => $recordArray) {
            $fileIndexFactory = GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Domain\\Factory\\FileIndexFactory',
                $this->getLocalDriver($storages[$storageIndex]),
                $this->getForeignDriver($storages[$storageIndex])
            );
            foreach ($recordArray as $record) {
                if ($record->hasLocalProperty('identifier')) {
                    $localIdentifier = $record->getLocalProperty('identifier');
                } else {
                    $localIdentifier = $record->getForeignProperty('identifier');
                }
                if ($record->hasForeignProperty('identifier')) {
                    $foreignIdentifier = $record->getForeignProperty('identifier');
                } else {
                    $foreignIdentifier = $record->getLocalProperty('identifier');
                }
                $fileIndexFactory->updateFileIndexInfo($record, $localIdentifier, $foreignIdentifier);
                $record->addAdditionalProperty('isAuthoritative', true);
            }
        }
    }

    /**
     * @param array $storages
     * @param RecordInterface[][] $sortedRecords
     */
    protected function prefetchForeignInformationFiles(array $storages, array $sortedRecords)
    {
        $foreignIdentifiers = array();
        foreach ($sortedRecords as $storageIndex => $recordArray) {
            foreach ($recordArray as $record) {
                if ($record->hasForeignProperty('identifier')) {
                    $foreignIdentifier = $record->getForeignProperty('identifier');
                } else {
                    $foreignIdentifier = $record->getLocalProperty('identifier');
                }
                $foreignIdentifiers[$storageIndex][] = $foreignIdentifier;
            }
        }
        foreach ($foreignIdentifiers as $storageIndex => $identifierArray) {
            $this->getForeignDriver($storages[$storageIndex])->batchPrefetchFiles($identifierArray);
        }
    }

    /**
     * @param ResourceStorage $localStorage
     * @return DriverInterface
     */
    protected function getLocalDriver(ResourceStorage $localStorage)
    {
        $driverProperty = new PropertyReflection(get_class($localStorage), 'driver');
        $driverProperty->setAccessible(true);
        return $driverProperty->getValue($localStorage);
    }

    /**
     * @param ResourceStorage $localStorage
     * @return RemoteFileAbstractionLayerDriver
     */
    protected function getForeignDriver(ResourceStorage $localStorage)
    {
        $foreignDriver = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Driver\\RemoteFileAbstractionLayerDriver'
        );
        $foreignDriver->setStorageUid($localStorage->getUid());
        $foreignDriver->initialize();
        return $foreignDriver;
    }
}