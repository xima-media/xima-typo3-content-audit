<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use Xima\XimaTypo3ContentAudit\Service\PagePreviewUrlProvider;

class RecentChangesDataProvider implements ListDataProviderInterface
{
    /**
    * Records created within this many days are marked as »New«
    */
    protected const NEW_THRESHOLD_DAYS = 7;

    private const DISPLAY_LIMIT = 20;

    /**
    * @var array<int>
    */
    protected array $excludePageUids = [];

    public function __construct(
        protected readonly PagePreviewUrlProvider $previewUrlProvider,
        private readonly \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool
    ) {
    }

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
        $matchingRecords = $this->fetchMatchingItems();

        // Check if user has access to edit the page (also covers content elements on that page)
        $accessibleRecords = [];
        foreach ($matchingRecords as $record) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($record, 2)) { // 2 = edit page
                continue;
            }
            $accessibleRecords[] = $record;
            if (count($accessibleRecords) >= self::DISPLAY_LIMIT) {
                break;
            }
        }

        return $this->fetchRecordDetails($accessibleRecords);
    }

    /**
    * Fetch raw record list
    *
    * @return list<array<string, mixed>>
    */
    private function fetchMatchingItems(): array
    {
        $connection = $this->connectionPool->getConnectionForTable('pages');
        $excludePageUids = empty($this->excludePageUids) ? [0] : $this->excludePageUids; // »0« workaround for valid sql

        // TYPO3 QueryBuilder does not support UNION queries directly
        // Fallback to raw SQL query for now and restore the query builder later if possible
        $sql = <<<SQL
            SELECT * FROM (
                SELECT
                    'page' AS recordType,
                    p.uid AS uid,
                    p.uid AS pageUid,
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
            ORDER BY changed DESC, recordType ASC, uid ASC
            SQL;

        return $connection->executeQuery(
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
    }

    /**
    * Enrich already access-checked records with the remaining display columns
    *
    * @param list<array<string, mixed>> $records
    * @return list<array<string, mixed>>
    */
    private function fetchRecordDetails(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $pageUids = [];
        $contentUids = [];
        foreach ($records as $record) {
            if ($record['recordType'] === 'page') {
                $pageUids[] = $record['uid'];
            } else {
                $contentUids[] = $record['uid'];
            }
        }

        $connection = $this->connectionPool->getConnectionForTable('pages');
        $selects = [];
        $params = [];
        $types = [];

        if ([] !== $pageUids) {
            $selects[] = <<<SQL
                SELECT 'page' AS recordType, uid, title AS recordTitle, slug AS pageSlug
                FROM pages
                WHERE uid IN (:pageUids)
                SQL;
            $params['pageUids'] = $pageUids;
            $types['pageUids'] = Connection::PARAM_INT_ARRAY;
        }

        if ([] !== $contentUids) {
            $selects[] = <<<SQL
                SELECT 'content' AS recordType, c.uid AS uid, c.header AS recordTitle, p.slug AS pageSlug
                FROM tt_content AS c
                INNER JOIN pages AS p ON p.uid = c.pid
                WHERE c.uid IN (:contentUids)
                SQL;
            $params['contentUids'] = $contentUids;
            $types['contentUids'] = Connection::PARAM_INT_ARRAY;
        }

        $rows = $connection->executeQuery(implode(PHP_EOL . 'UNION ALL' . PHP_EOL, $selects), $params, $types)
            ->fetchAllAssociative();

        $detailsByKey = [];
        foreach ($rows as $row) {
            $detailsByKey[$row['recordType'] . ':' . $row['uid']] = $row;
        }

        $isNewThreshold = time() - self::NEW_THRESHOLD_DAYS * 86400;
        $results = [];
        foreach ($records as $record) {
            $key = $record['recordType'] . ':' . $record['uid'];
            $record = array_merge($record, $detailsByKey[$key] ?? []);
            $record['action'] = ((int)$record['created'] === (int)$record['changed']) ? 'created' : 'updated';
            $record['editorName'] = $this->resolveEditorName($record);
            $record['isNew'] = (int)$record['created'] >= $isNewThreshold;
            $record['previewUrl'] = $this->previewUrlProvider->getUrl((int)$record['pageUid']);
            $results[] = $record;
        }

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

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_log');
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

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $username = $queryBuilder
            ->select('username')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return $username ?: null;
    }
}
