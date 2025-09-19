<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class MergedPageChangeDataProvider implements ListDataProviderInterface
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

        // TYPO3 QueryBuilder does not support subqueries in JOINs directly
        // Fallback to raw SQL query for now and restore the query builder later if possible
        $sql = <<<SQL
SELECT
    p.uid,
    p.title as pageTitle,
    p.slug as pageSlug,
    p.crdate as created,
    p.tstamp as lastPageChange,
    IFNULL(content.lastContentChange, p.tstamp) as lastContentChange,
    GREATEST(IFNULL(content.lastContentChange, 0), p.tstamp) AS updated
FROM pages AS p
LEFT JOIN (
    SELECT pid, MAX(tstamp) AS lastContentChange
    FROM tt_content
    WHERE deleted = 0
    GROUP BY pid
) AS content ON content.pid = p.uid
WHERE
    p.sys_language_uid = 0
    AND p.deleted = 0
    AND p.hidden = 0
    AND p.doktype IN (1, 4)
    AND p.uid NOT IN (:uids)
    AND GREATEST(IFNULL(content.lastContentChange, 0), p.tstamp) < :timestamp
ORDER BY updated ASC
LIMIT 20
SQL;

        $results = $connection->executeQuery(
            $sql,
            [
                'timestamp' => strtotime('-180 days'),
                'uids' => empty($this->excludePageUids) ? [0] : $this->excludePageUids, // »0« workaround for valid sql
            ],
            [
                'timestamp' => Connection::PARAM_INT,
                'uids' => Connection::PARAM_INT_ARRAY,
            ]
        )->fetchAllAssociative();

        // Check if user has access to edit page record
        foreach ($results as $key => $page) {
            if (!$GLOBALS['BE_USER']->doesUserHaveAccess($page, 2)) { // 2 = edit page
                unset($results[$key]);
            }
        }

        return $results;
    }
}
