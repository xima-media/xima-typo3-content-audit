<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class HiddenPagesDataProvider implements ListDataProviderInterface
{
    /**
    * @var array<int>
    */
    protected array $excludePageUids = [];

    /**
    * @param array<int> $excludePageUids
    */
    public function setExcludePageUids(array $excludePageUids): void
    {
        $this->excludePageUids = $excludePageUids;
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        // Remove TYPO3 default "hidden" restriction to also find hidden pages
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $queryBuilder
            ->select(
                'uid',
                'title as pageTitle',
                'slug as pageSlug',
                'crdate as created',
                'tstamp as updated',
                'perms_userid',
                'perms_groupid',
                'perms_user',
                'perms_group',
                'perms_everybody'
            )
            ->from('pages')
            // Select only pages and shortcuts, no folders etc
            ->where(
                $queryBuilder->expr()->in(
                    'doktype',
                    $queryBuilder->createNamedParameter([1, 4], Connection::PARAM_INT_ARRAY)
                )
            )
            ->setMaxResults(20)
            ->orderBy('tstamp', 'ASC');

        // Only select hidden (not deleted) pages not updated for x days
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
            $queryBuilder->expr()->lt(
                'tstamp',
                $queryBuilder->createNamedParameter(strtotime('-365 days'), Connection::PARAM_INT)
            )
        );

        // Add optional page exclusions
        if (!empty($this->excludePageUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    $queryBuilder->createNamedParameter($this->excludePageUids, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $hiddenCountQueryBuilder = clone $queryBuilder;
        $hiddenCountQueryBuilder->count('uid');
        $hiddenCountQueryBuilder->resetQueryPart('orderBy'); // Resets the limit
        $hiddenCount = (int)$hiddenCountQueryBuilder->executeQuery()->fetchOne();

        $totalCountQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $totalCountQueryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $totalCount = (int)$totalCountQueryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $totalCountQueryBuilder->expr()->in(
                    'doktype',
                    $totalCountQueryBuilder->createNamedParameter([1, 4], Connection::PARAM_INT_ARRAY)
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
            'hiddenCount' => $hiddenCount,
            'totalCount' => $totalCount,
            'results' => $results,
        ];
    }
}
