<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Component\Core\Publisher\PublisherService;
use In2code\In2publishCore\Component\Core\Publisher\PublishingContext;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilder;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Controller\Traits\CommonViewVariables;
use In2code\In2publishCore\Controller\Traits\ControllerFilterStatus;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Controller\Traits\DeactivateErrorFlashMessage;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchWasNotStartedException;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\SimpleStopwatch;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Error\FailureCollector;
use In2code\In2publishCore\Service\Permission\PermissionService;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\LogUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_keys;
use function implode;
use function is_int;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Content publishing Controller. Any action is for the "Publish Records" Backend module "m1"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecordController extends ActionController
{
    use ControllerFilterStatus;
    use ControllerModuleTemplate;
    use DeactivateErrorFlashMessage;
    use CommonViewVariables;

    protected FailureCollector $failureCollector;
    protected PermissionService $permissionService;
    protected RecordTreeBuilder $recordTreeBuilder;
    protected PublisherService $publisherService;
    protected RecordIndex $recordIndex;
    protected SimpleStopwatch $simpleStopwatch;

    public function injectFailureCollector(FailureCollector $failureCollector): void
    {
        $this->failureCollector = $failureCollector;
    }

    public function injectPermissionService(PermissionService $permissionService): void
    {
        $this->permissionService = $permissionService;
    }

    public function injectRecordTreeBuilder(RecordTreeBuilder $recordTreeBuilder): void
    {
        $this->recordTreeBuilder = $recordTreeBuilder;
    }

    public function injectPublisherService(PublisherService $publisherService): void
    {
        $this->publisherService = $publisherService;
    }

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $pageRenderer->addInlineLanguageLabelFile(
            'EXT:in2publish_core/Resources/Private/Language/locallang_js.xlf'
        );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/BackendModule');
        $pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false
        );
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    public function injectSimpleStopwatch(SimpleStopwatch $simpleStopwatch): void
    {
        $this->simpleStopwatch = $simpleStopwatch;
    }

    public function initializeIndexAction(): void
    {
        /** @var BackendUserAuthentication $BE_USER */
        $BE_USER = $GLOBALS['BE_USER'];
        $data = $BE_USER->getModuleData('tx_in2publishcore_m1') ?? ['pageRecursionLimit' => 1];
        if ($this->request->hasArgument('pageRecursionLimit')) {
            $pageRecursionLimit = (int)$this->request->getArgument('pageRecursionLimit');
            $data['pageRecursionLimit'] = $pageRecursionLimit;
            $BE_USER->pushModuleData('tx_in2publishcore_m1', $data);
        } else {
            $this->request->setArgument('pageRecursionLimit', $data['pageRecursionLimit'] ?? 1);
        }

        $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menu = $menuRegistry->makeMenu();
        $menu->setIdentifier('depth');
        $menu->setLabel(LocalizationUtility::translate('m1.page_recursion', 'in2publish_core'));
        for ($i = 0; $i <= 10; $i++) {
            $menuItem = $menu->makeMenuItem();
            $menuItem->setActive($i === $data['pageRecursionLimit']);
            if ($i > 1) {
                $title = LocalizationUtility::translate('m1.page_recursion.depths', 'in2publish_core', [$i]);
            } else {
                $title = LocalizationUtility::translate('m1.page_recursion.depth', 'in2publish_core', [$i]);
            }
            $menuItem->setTitle($title);
            $menuItem->setHref($this->uriBuilder->uriFor('index', ['pageRecursionLimit' => $i]));
            $menu->addMenuItem($menuItem);
        }
        $menuRegistry->addMenu($menu);
    }

    /**
     * Create a Record instance of the current selected page
     * If none is chosen, a Record with uid = 0 is created which
     * represents the instance root
     */
    public function indexAction(int $pageRecursionLimit): ResponseInterface
    {
        $pid = BackendUtility::getPageIdentifier();
        if (!is_int($pid)) {
            $pid = 0;
        }
        $request = new RecordTreeBuildRequest('pages', $pid, $pageRecursionLimit);
        $recordTree = $this->recordTreeBuilder->buildRecordTree($request);

        //$traverser = new RecordTreeTraverser();
        //$traverser->addVisitor(function (string $event, Record $record) {
        //    if ($event !== RecordTreeTraverser::EVENT_ENTER) {
        //        return;
        //    }
        //    $isPublishable = $record->isPublishable();
        //});
        //$traverser->run($recordTree);

        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($recordTree, __FILE__ . '@' . __LINE__, 20, false, true, false, [], ['temporaryCacheDirectory']);die(__FILE__ . '@' . __LINE__);
        //
        //$reasons = $this->recordDependencyResolver->getUnmetDependencies($recordTree);
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($reasons, __FILE__ . '@' . __LINE__, 20, false, true, false, [], ['temporaryCacheDirectory']);die(__FILE__ . '@' . __LINE__);

        //foreach ($deps as &$dep) {
        //    $toClass = $dep->getToClassifier();
        //    $toId = $dep->getToId();
        //    $fromRecord = $dep->getRecord();
        //    $toRecord = $this->recordIndex->getRecord($toClass, $toId);
        //    if (null === $toRecord) {
        //        $toRecord = new class($toClass, $toId) {
        //            private $toClass;
        //            private $toId;
        //
        //            public function __construct($toClass, $toId)
        //            {
        //                $this->toClass = $toClass;
        //                $this->toId = $toId;
        //            }
        //
        //            public function __toString(): string
        //            {
        //                return $this->toClass . ':' . $this->toId;
        //            }
        //
        //            public function getClassification(): string
        //            {
        //                return $this->toClass;
        //            }
        //        };
        //    }
        //    $dep = (string)$fromRecord
        //        . '('
        //        . $fromRecord->getClassification()
        //        . '['
        //        . $fromRecord->getId()
        //        . '])'
        //        . ' -> '
        //        . (string)$toRecord
        //        . '('
        //        . $toRecord->getClassification()
        //        . '['
        //        . $toRecord->getId()
        //        . '])'
        //        . ': '
        //        . $dep->getReason();
        //}
        //unset($dep);
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(
        //    $deps,
        //    __FILE__ . '@' . __LINE__,
        //    20,
        //    false,
        //    true,
        //    false,
        //    [],
        //    []
        //);
        //die(__FILE__ . '@' . __LINE__);

        $this->view->assign('recordTree', $recordTree);
        return $this->htmlResponse();
    }

    /**
     * Check if user is allowed to publish
     *
     * @throws In2publishCoreException
     */
    public function initializePublishRecordAction(): void
    {
        if (!$this->permissionService->isUserAllowedToPublish()) {
            throw new In2publishCoreException('You are not allowed to publish', 1435306780);
        }
    }

    public function publishRecordAction(int $id): void
    {
        $request = new RecordTreeBuildRequest('pages', $id, 0);
        $recordTree = $this->recordTreeBuilder->buildRecordTree($request);

        $publishingContext = new PublishingContext($recordTree);
        $this->publisherService->publish($publishingContext);

        $this->addFlashMessagesAndRedirectToIndex();
    }

    /**
     * toggle filter status and save the filter status
     * in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     */
    public function toggleFilterStatusAction(string $filter): ResponseInterface
    {
        $return = $this->toggleFilterStatus('in2publish_filter_records_', $filter);
        return $this->jsonResponse(json_encode($return, JSON_THROW_ON_ERROR));
    }

    /**
     * Add success message and redirect to indexAction
     *
     * @throws StopActionException
     */
    protected function addFlashMessagesAndRedirectToIndex(): void
    {
        $failures = $this->failureCollector->getFailures();

        try {
            $executionTime = $this->simpleStopwatch->getTime();
        } catch (StopwatchWasNotStartedException $e) {
            $executionTime = 'Timer was never started';
        }
        if (empty($failures)) {
            $message = '';
            $title = LocalizationUtility::translate('record_published', 'in2publish_core', [$executionTime]);
            $severity = AbstractMessage::OK;
        } else {
            $message = '"' . implode('"; "', array_keys($failures)) . '"';
            $title = LocalizationUtility::translate('record_publishing_failure', 'in2publish_core', [$executionTime]);
            $mostCriticalLogLevel = $this->failureCollector->getMostCriticalLogLevel();
            $severity = LogUtility::translateLogLevelToSeverity($mostCriticalLogLevel);
        }
        $this->addFlashMessage($message, $title, $severity);

        $this->redirect('index', 'Record');
    }
}
