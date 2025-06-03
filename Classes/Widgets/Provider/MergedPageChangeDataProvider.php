<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        $queryBuilder
            ->select(
                'uid',
                'crdate as created',
                'tstamp as updated',
                'title as pageTitle'
            )
            ->from('pages')
            // Select only pages and shortcuts, no folders etc
            ->where(
                $queryBuilder->expr()->in(
                    'doktype',
                    $queryBuilder->createNamedParameter([1, 4], \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY)
                )
            )
            ->setMaxResults(20)
            ->orderBy('tstamp', 'ASC');

        // Only show pages older than x days
        $queryBuilder->andWhere(
            $queryBuilder->expr()->lt(
                'crdate',
                $queryBuilder->createNamedParameter(strtotime('-180 days'), \PDO::PARAM_INT)
            )
        );

        // Add optional page exclusions
        if (!empty($this->excludePageUids)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        $this->excludePageUids,
                        \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY
                    )
                )
            );
        }

        $results = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        return $results;
    }
}
