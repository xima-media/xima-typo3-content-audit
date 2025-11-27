<?php

// Assign this preset to users with TSconfig:
// options.dashboard.dashboardPresetsForNewUsers = tx_ximatypo3contentaudit_dashboard

return [
    'tx_ximatypo3contentaudit_dashboard' => [
        'title' => 'LLL:EXT:xima_typo3_content_audit/Resources/Private/Language/locallang.xlf:dashboard.title',
        'description' => 'LLL:EXT:xima_typo3_content_audit/Resources/Private/Language/locallang.xlf:dashboard.description',
        'iconIdentifier' => 'content-audit-widgets-daily-mission',
        'defaultWidgets' => [
            'contentAuditDailyMission',
            'contentAuditStalePages',
            'contentAuditFreshPages',
            'contentAuditHiddenPages',
            'contentAuditHiddenContent',
            'contentAuditMissingImageFields',
            'contentAuditBrokenLinks',
        ],
        'showInWizard' => true,
    ],
];
