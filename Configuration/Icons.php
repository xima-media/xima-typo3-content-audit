<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'content-audit-widgets-stale-pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsStalePages.svg',
    ],
    'content-audit-widgets-fresh-pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsFreshPages.svg',
    ],
    'content-audit-widgets-hidden-pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsHiddenPages.svg',
    ],
    'content-audit-widgets-hidden-content' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsHiddenContent.svg',
    ],
    'content-audit-widgets-missing-image-fields' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsMissingImageFields.svg',
    ],
    'content-audit-widgets-broken-links' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsBrokenLinks.svg',
    ],
    'content-audit-widgets-daily-mission' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsDailyMission.svg',
    ],
    'content-audit-widgets-empty-pages' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:xima_typo3_content_audit/Resources/Public/Icons/WidgetsEmptyPages.svg',
    ],
];
