<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\TaskExecutionService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Throwable;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function implode;
use function is_bool;

/**
 * Class AbstractController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractController extends ActionController
{
    public const BLANK_ACTION = 'blankAction';

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser = null;

    /**
     * AbstractConfiguredController constructor.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct()
    {
        parent::__construct();
        $this->backendUser = $GLOBALS['BE_USER'];
    }

    /**
     * Sets action to blankAction if the foreign DB is not reachable. Prevents further errors.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function initializeAction()
    {
        parent::initializeAction();
        if (static::class !== ToolsController::class) {
            try {
                if (null !== DatabaseUtility::buildForeignDatabaseConnection()) {
                    return;
                }
                $this->addFlashMessage(
                    LocalizationUtility::translate('error_not_connected', 'in2publish_core'),
                    '',
                    AbstractMessage::ERROR
                );
            } catch (Throwable $exception) {
                $this->addFlashMessage(
                    (string)$exception,
                    LocalizationUtility::translate('error_connecting', 'in2publish_core')
                    . ': ' . $exception->getMessage(),
                    AbstractMessage::ERROR
                );
            }
            $this->actionMethodName = static::BLANK_ACTION;
            $this->arguments = $this->objectManager->get(Arguments::class);
        }
    }

    /**
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $localDbAvailable = null !== DatabaseUtility::buildLocalDatabaseConnection();
        try {
            $foreignDbAvailable = null !== DatabaseUtility::buildForeignDatabaseConnection();
        } catch (Throwable $exception) {
            // Exception is already caught and processed in the initializeAction
            $foreignDbAvailable = false;
        }
        $this->view->assign('localDatabaseConnectionAvailable', $localDbAvailable);
        $this->view->assign('foreignDatabaseConnectionAvailable', $foreignDbAvailable);
        $this->view->assign('publishingAvailable', $localDbAvailable && $foreignDbAvailable);
    }

    /**
     * Dummy Method to use when an error occurred. This Method must never throw an exception.
     */
    public function blankAction()
    {
    }

    /**
     * @param string $filterName
     * @param string $status
     * @param string $action
     *
     * @throws StopActionException
     */
    protected function toggleFilterStatusAndRedirect($filterName, $status, $action)
    {
        $currentStatus = $this->backendUser->getSessionData($filterName . $status);
        if (!is_bool($currentStatus)) {
            $currentStatus = false;
        }
        $this->backendUser->setAndSaveSessionData($filterName . $status, !$currentStatus);
        try {
            $this->redirect($action);
        } catch (UnsupportedRequestTypeException $e) {
        }
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function runTasks()
    {
        $taskExecutionService = GeneralUtility::makeInstance(TaskExecutionService::class);
        $response = $taskExecutionService->runTasks();
        if (!$response->isSuccessful()) {
            $this->addFlashMessage(
                implode('<br/>', $response->getOutput()) . implode('<br/>', $response->getErrors()),
                LocalizationUtility::translate('publishing.tasks_failure', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }
    }
}
