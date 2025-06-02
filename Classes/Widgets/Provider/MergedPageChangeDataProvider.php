<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class MergedPageChangeDataProvider implements ListDataProviderInterface
{
    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        $results = $queryBuilder
            ->select(
                'uid',
                'crdate as created',
                'tstamp as updated',
                'title as pageTitle'
            )
            ->from('pages')
            // excludes deleted/hidden pages by default
            ->setMaxResults(20)
            ->orderBy('tstamp', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $results;
    }
}
