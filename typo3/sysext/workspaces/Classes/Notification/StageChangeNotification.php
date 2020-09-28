<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Workspaces\Notification;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Responsible for sending out emails when one or multiple records have been changed / sent to the next stage.
 *
 * Relevant options are "tx_workspaces.emails.*" via userTS / pageTS.
 *
 * @internal This is a concrete implementation of sending out emails, and not part of the public TYPO3 Core API
 */
class StageChangeNotification
{
    /**
     * @var StagesService
     */
    protected $stagesService;

    /**
     * @var PreviewUriBuilder
     */
    protected $previewUriBuilder;

    /**
     * @var Mailer
     */
    protected $mailer;

    public function __construct()
    {
        $this->stagesService = GeneralUtility::makeInstance(StagesService::class);
        $this->previewUriBuilder = GeneralUtility::makeInstance(PreviewUriBuilder::class);
        $this->mailer = GeneralUtility::makeInstance(Mailer::class);
    }

    /**
     * Send an email notification to users in workspace in multiple languages, depending on each BE users' language
     * preference.
     *
     * @param array $workspaceRecord
     * @param int $stageId Next Stage Number
     * @param array $affectedElements List of element names (table / uid pairs)
     * @param string $comment User comment sent along with action
     * @param array $recipients List of recipients to notify, list is generated by workspace extension module
     * @param BackendUserAuthentication $currentUser
     */
    public function notifyStageChange(array $workspaceRecord, int $stageId, array $affectedElements, string $comment, array $recipients, BackendUserAuthentication $currentUser): void
    {
        [$elementTable, $elementUid] = reset($affectedElements);
        $elementUid = (int)$elementUid;
        $elementRecord = (array)BackendUtility::getRecord($elementTable, $elementUid);
        $recordTitle = BackendUtility::getRecordTitle($elementTable, $elementRecord);
        $pageUid = $this->findFirstPageId($elementTable, $elementUid, $elementRecord);

        $emailConfig = BackendUtility::getPagesTSconfig($pageUid)['tx_workspaces.']['emails.'] ?? [];
        $emailConfig = GeneralUtility::removeDotsFromTS($emailConfig);
        $viewPlaceholders = [
            'pageId' => $pageUid,
            'workspace' => $workspaceRecord,
            'rootLine' => BackendUtility::getRecordPath($pageUid, '', 20),
            'currentUser' => $currentUser->user,
            'additionalMessage' => $comment,
            'recordTitle' => $recordTitle,
            'affectedElements' => $affectedElements,
            'nextStage' => $this->stagesService->getStageTitle($stageId),
            'comparisonView' => (string)$this->previewUriBuilder->buildUriForWorkspaceSplitPreview($pageUid)
        ];

        if ($emailConfig['stageChangeNotification']['generatePreviewLink']) {
            $viewPlaceholders['previewLink'] = $this->previewUriBuilder->buildUriForPage($pageUid, 0);
        }

        $sentEmails = [];
        foreach ($recipients as $recipientData) {
            // don't send an email twice
            if (in_array($recipientData['email'], $sentEmails, true)) {
                continue;
            }
            $sentEmails[] = $recipientData['email'];
            $this->sendEmail($recipientData, $emailConfig, $viewPlaceholders);
        }
    }

    /**
     * As it is possible that multiple elements are sent out, or multiple pages, the first "real" page ID is found.
     *
     * @param string $elementTable the table of the first element found
     * @param int $elementUid the uid of the first element in the list
     * @param array $elementRecord the full record
     * @return int the corresponding page ID
     */
    protected function findFirstPageId(string $elementTable, int $elementUid, array $elementRecord): int
    {
        if ($elementTable === 'pages') {
            return $elementUid;
        }
        BackendUtility::fixVersioningPid($elementTable, $elementRecord);
        return (int)$elementRecord['pid'];
    }

    /**
     * Send one email to a specific person, apply multi-language possibilities for sending this email out.
     *
     * @param array $recipientData
     * @param array $emailConfig
     * @param array $variablesForView
     */
    protected function sendEmail(array $recipientData, array $emailConfig, array $variablesForView): void
    {
        $templatePaths = new TemplatePaths(array_replace_recursive($GLOBALS['TYPO3_CONF_VARS']['MAIL'], $emailConfig));
        $emailObject = GeneralUtility::makeInstance(FluidEmail::class, $templatePaths);
        $emailObject
            ->to(new Address($recipientData['email'], $recipientData['realName'] ?? ''))
            // Will be overridden by the template
            ->subject('TYPO3 Workspaces: Stage Change')
            ->setTemplate('StageChangeNotification')
            ->assignMultiple($variablesForView)
            ->assign('language', $recipientData['lang'] ?? 'default');

        // Injecting normalized params
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            $emailObject->setRequest($GLOBALS['TYPO3_REQUEST']);
        }
        if ($emailConfig['format']) {
            $emailObject->format($emailConfig['format']);
        }
        if (!empty($emailConfig['senderEmail']) && GeneralUtility::validEmail($emailConfig['senderEmail'])) {
            $emailObject->from(new Address($emailConfig['senderEmail'], $emailConfig['senderName'] ?? ''));
        }
        $this->mailer->send($emailObject);
    }
}
