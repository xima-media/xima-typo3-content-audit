<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class RecentChangesDataProvider implements ListDataProviderInterface
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');

        $excludePageUids = empty($this->excludePageUids) ? [0] : $this->excludePageUids; // »0« workaround for valid sql

        // TYPO3 QueryBuilder does not support UNION queries directly
        // Fallback to raw SQL query for now and restore the query builder later if possible
        $sql = <<<SQL
SELECT * FROM (
    SELECT
        'page' AS recordType,
        p.uid AS uid,
        p.uid AS pageUid,
        p.title AS recordTitle,
        p.slug AS pageSlug,
        p.crdate AS created,
        p.tstamp AS changed,
        p.perms_userid,
        p.perms_groupid,
        p.perms_user,
        p.perms_group,
        p.perms_everybody
    FROM pages AS p
    WHERE
        p.sys_language_uid = 0
        AND p.deleted = 0
        AND p.hidden = 0
        AND p.doktype IN (1, 4)
        AND p.uid NOT IN (:pageUids)

    UNION ALL

    SELECT
        'content' AS recordType,
        c.uid AS uid,
        c.pid AS pageUid,
        c.header AS recordTitle,
        p.slug AS pageSlug,
        c.crdate AS created,
        c.tstamp AS changed,
        p.perms_userid,
        p.perms_groupid,
        p.perms_user,
        p.perms_group,
        p.perms_everybody
    FROM tt_content AS c
    INNER JOIN pages AS p ON p.uid = c.pid
    WHERE
        c.sys_language_uid = 0
        AND c.deleted = 0
        AND c.hidden = 0
        AND p.deleted = 0
        AND p.hidden = 0
        AND p.doktype IN (1, 4)
        AND c.pid NOT IN (:contentPageUids)
) AS combined
ORDER BY changed DESC
LIMIT 20
SQL;

        $results = $connection->executeQuery(
            $sql,
            [
                'pageUids' => $excludePageUids,
                'contentPageUids' => $excludePageUids,
            ],
            [
                'pageUids' => Connection::PARAM_INT_ARRAY,
                'contentPageUids' => Connection::PARAM_INT_ARRAY,
            ]
        )->fetchAllAssociative();

        // Check if user has access to edit the page (also covers content elements on that page)
        foreach ($results as $key => $record) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($record, 2)) { // 2 = edit page
                unset($results[$key]);
            }
        }
        $results = array_values($results);

        // Add editor name to action
        $results = array_map(function (array $record): array {
            $record['action'] = ((int)$record['created'] === (int)$record['changed']) ? 'created' : 'updated';
            $record['editorName'] = $this->resolveEditorName($record);
            return $record;
        }, $results);

        return $results;
    }

    /**
    * @param array<string, mixed> $record
    */
    protected function resolveEditorName(array $record): ?string
    {
        // Neither »pages« nor »tt_content« track who created or last edited a record
        // (both lost their cruser_id column) - only the system log does
        $table = $record['recordType'] === 'page' ? 'pages' : 'tt_content';
        $sortDirection = $record['action'] === 'created' ? 'ASC' : 'DESC';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $editorId = $queryBuilder
            ->select('userid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter((int)$record['uid'], Connection::PARAM_INT))
            )
            ->orderBy('tstamp', $sortDirection)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $editorId ? $this->getUsername((int)$editorId) : null;
    }

    protected function getUsername(int $userId): ?string
    {
        if ($userId === 0) {
            return null;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $username = $queryBuilder
            ->select('username')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return $username ?: null;
    }
}
