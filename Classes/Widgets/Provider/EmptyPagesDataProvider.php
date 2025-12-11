<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class EmptyPagesDataProvider implements ListDataProviderInterface
{
    /**
    * @var array<int>
    */
    protected array $excludePageUids = [];

    /**
    * @var array<int>
    */
    protected array $allowedPageTypes = [1];

    /**
    * @param array<int> $excludePageUids
    */
    public function setExcludePageUids(array $excludePageUids): void
    {
        $this->excludePageUids = $excludePageUids;
    }

    /**
    * @param array<int> $allowedPageTypes
    */
    public function setAllowedPageTypes(array $allowedPageTypes): void
    {
        $this->allowedPageTypes = $allowedPageTypes;
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        $queryBuilder
            ->select(
                'pages.uid',
                'pages.title as pageTitle',
                'pages.slug as pageSlug',
                'pages.crdate as created',
                'pages.tstamp as updated',
                'pages.perms_userid',
                'pages.perms_groupid',
                'pages.perms_user',
                'pages.perms_group',
                'pages.perms_everybody'
            )
            ->addSelectLiteral('COUNT(' . $queryBuilder->quoteIdentifier('content.uid') . ') as content_count')
            ->from('pages')
            // Select only configured page types
            ->where(
                $queryBuilder->expr()->in(
                    'pages.doktype',
                    $queryBuilder->createNamedParameter($this->allowedPageTypes, Connection::PARAM_INT_ARRAY)
                )
            )
            ->leftJoin(
                'pages',
                'tt_content',
                'content',
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('content.pid', $queryBuilder->quoteIdentifier('pages.uid')),
                    $queryBuilder->expr()->eq('content.hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('content.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
            )
            ->groupBy('pages.uid')
            ->having('content_count = 0')
            ->setMaxResults(20)
            ->orderBy('pages.tstamp', 'DESC');

        // Add optional page exclusions
        if (!empty($this->excludePageUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'pages.uid',
                    $queryBuilder->createNamedParameter($this->excludePageUids, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $emptyCountQueryBuilder = clone $queryBuilder;
        $emptyCountQueryBuilder->setMaxResults(PHP_INT_MAX);  // Reset the cloned limit
        // @todo When dropping support for TYPO3 11 we may use ->resetOrderBy() instead
        // We can't use ->count() here because it would remove the content_count column needed by HAVING
        // Execute the grouped query and count the number of rows instead
        $emptyCount = count($emptyCountQueryBuilder->executeQuery()->fetchAllAssociative());

        $totalCountQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $totalCount = (int)$totalCountQueryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $totalCountQueryBuilder->expr()->in(
                    'doktype',
                    $totalCountQueryBuilder->createNamedParameter($this->allowedPageTypes, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery()
            ->fetchOne();

        $results = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        // Check if user has access to edit page record
        foreach ($results as $key => $page) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($page, 2)) { // 2 = edit page
                unset($results[$key]);
            }
        }

        return [
            'emptyCount' => $emptyCount,
            'totalCount' => $totalCount,
            'results' => $results,
        ];
    }
}
