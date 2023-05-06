<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function () {

    /**
     * PageTSConfig to set up new field
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '@import \'EXT:uppload/Configuration/TsConfig/Page/Page.tsconfig\''
    );

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    /*
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
    );
    $signalSlotDispatcher->connect(
        'In2code\Powermail\Controller\FormController',
        'createActionBeforeRenderView',
        'EHAERER\Uppload\Signal\SaveImage',
        'saveImage',
        FALSE
    );*/

});
