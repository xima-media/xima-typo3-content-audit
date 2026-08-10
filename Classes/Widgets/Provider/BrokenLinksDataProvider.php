<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use Xima\XimaTypo3ContentAudit\Service\PagePreviewUrlProvider;

class BrokenLinksDataProvider implements ListDataProviderInterface
{
    public function __construct(
        protected readonly PagePreviewUrlProvider $previewUrlProvider, private readonly \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool
    ) {
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_linkvalidator_link');
        $queryBuilder
            ->select(
                'p.uid',
                'p.title as pageTitle',
                'p.slug as pageSlug',
                'p.tstamp as updated',
                'p.perms_userid',
                'p.perms_groupid',
                'p.perms_user',
                'p.perms_group',
                'p.perms_everybody'
            )
            ->addSelectLiteral('COUNT(l.uid) as broken_link_count')
            ->from('tx_linkvalidator_link', 'l')
            ->join(
                'l',
                'pages',
                'p',
                $queryBuilder->expr()->eq('l.record_pid', $queryBuilder->quoteIdentifier('p.uid'))
            )
            ->where(
                // Only internal links (type 'db' - includes both page and record links)
                $queryBuilder->expr()->eq(
                    'l.link_type',
                    $queryBuilder->createNamedParameter('db', Connection::PARAM_STR)
                )
            )
            ->groupBy('p.uid')
            ->orderBy('broken_link_count', 'DESC')
            ->addOrderBy('p.tstamp', 'DESC')
            ->setMaxResults(20);

        $results = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        // Check if user has access to edit page record, add frontend preview URL
        foreach ($results as $key => $page) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($page, 2)) { // 2 = edit page
                unset($results[$key]);
                continue;
            }
            $results[$key]['previewUrl'] = $this->previewUrlProvider->getUrl((int)$page['uid']);
        }

        return $results;
    }
}
