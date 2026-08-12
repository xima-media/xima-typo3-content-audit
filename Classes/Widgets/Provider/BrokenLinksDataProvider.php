<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use Xima\XimaTypo3ContentAudit\Service\PagePreviewUrlProvider;

class BrokenLinksDataProvider implements ListDataProviderInterface
{
    private const DISPLAY_LIMIT = 20;

    public function __construct(
        protected readonly PagePreviewUrlProvider $previewUrlProvider,
        private readonly \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool
    ) {
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $matchingPages = $this->fetchMatchingItems();

        // Check if user has access to edit page record
        $accessiblePages = [];
        foreach ($matchingPages as $page) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($page, 2)) { // 2 = edit page
                continue;
            }
            $accessiblePages[] = $page;
            if (count($accessiblePages) >= self::DISPLAY_LIMIT) {
                break;
            }
        }

        return $this->fetchPageDetails($accessiblePages);
    }

    /**
    * Fetch raw page list
    *
    * @return list<array<string, mixed>>
    */
    private function fetchMatchingItems(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_linkvalidator_link');
        $queryBuilder
            ->select('p.uid', 'p.tstamp as updated', 'p.perms_userid', 'p.perms_groupid', 'p.perms_user', 'p.perms_group', 'p.perms_everybody')
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
            ->addOrderBy('updated', 'DESC')
            ->addOrderBy('p.uid', 'ASC');

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
    * Enrich already access-checked pages with the remaining display columns
    *
    * @param list<array<string, mixed>> $pages
    * @return list<array<string, mixed>>
    */
    private function fetchPageDetails(array $pages): array
    {
        if (empty($pages)) {
            return [];
        }

        $pageUids = array_column($pages, 'uid');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $queryBuilder
            ->select('uid', 'title as pageTitle', 'slug as pageSlug')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($pageUids, Connection::PARAM_INT_ARRAY))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $rowsByUid = array_column($rows, null, 'uid');

        $results = [];
        foreach ($pages as $page) {
            $page = array_merge($page, $rowsByUid[$page['uid']] ?? []);
            $page['previewUrl'] = $this->previewUrlProvider->getUrl((int)$page['uid']);
            $results[] = $page;
        }

        return $results;
    }
}
