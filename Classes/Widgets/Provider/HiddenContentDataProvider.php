<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class HiddenContentDataProvider implements ListDataProviderInterface
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

        // Remove TYPO3 default "hidden" restriction to also find hidden content elements
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class);

        $queryBuilder
            ->select(
                'content.uid',
                'content.pid',
                'content.header as contentTitle',
                'content.crdate as created',
                'content.tstamp as updated',
                'content.hidden',
                'page.uid as pageUid',
                'page.pid as pagePid',
                'page.slug as pageSlug',
                'page.perms_userid as pagePermsUserId',
                'page.perms_groupid as pagePermsGroupId',
                'page.perms_user as pagePermsUser',
                'page.perms_group as pagePermsGroup',
                'page.perms_everybody as pagePermsEverybody'
            )
            ->from('tt_content', 'content')
            ->innerJoin(
                'content',
                'pages',
                'page',
                'page.uid = content.pid'
            )
            ->setMaxResults(20)
            ->orderBy('content.tstamp', 'ASC');

        // Only select hidden (not deleted) content not updated for x days
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq('content.hidden', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
            $queryBuilder->expr()->lt(
                'content.tstamp',
                $queryBuilder->createNamedParameter(strtotime('-730 days'), Connection::PARAM_INT)
            )
        );

        // Add optional page exclusions
        if (!empty($this->excludePageUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'content.pid',
                    $queryBuilder->createNamedParameter($this->excludePageUids, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $hiddenCountQueryBuilder = clone $queryBuilder;
        $hiddenCountQueryBuilder->count('content.uid');
        $hiddenCountQueryBuilder->setMaxResults(PHP_INT_MAX); // Reset the cloned limit
        // @todo When dropping support for TYPO3 11 we may use ->resetOrderBy() instead
        $hiddenCount = (int)$hiddenCountQueryBuilder->executeQuery()->fetchOne();

        $totalCountQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $totalCountQueryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class);
        $totalCount = (int)$totalCountQueryBuilder
            ->count('uid')
            ->from('tt_content')
            ->executeQuery()
            ->fetchOne();

        $results = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        // Check if user has access to edit the content record
        if (!$GLOBALS['BE_USER']->check('tables_modify', 'tt_content')) {
            $results = [];
        }
        foreach ($results as $key => $content) {
            $pageRecord = [
                'uid' => (int)$content['pageUid'],
                'pid' => (int)$content['pagePid'],
                'perms_userid' => (int)$content['pagePermsUserId'],
                'perms_groupid' => (int)$content['pagePermsGroupId'],
                'perms_user' => (int)$content['pagePermsUser'],
                'perms_group' => (int)$content['pagePermsGroup'],
                'perms_everybody' => (int)$content['pagePermsEverybody'],
            ];
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($pageRecord, 2)) {
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
