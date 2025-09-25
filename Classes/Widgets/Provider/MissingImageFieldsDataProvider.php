<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class MissingImageFieldsDataProvider implements ListDataProviderInterface
{
    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder
            ->select(
                'meta.uid',
                'meta.file', // reference to sys_file
                'meta.title',
                'meta.description',
                'meta.alternative',
                'meta.copyright',
                'meta.sys_language_uid',
                'meta.tstamp',
                'meta.crdate',
                'file.identifier',
                'file.name',
                'file.mime_type'
            )
            ->from('sys_file_metadata', 'meta')
            ->innerJoin(
                'meta',
                'sys_file',
                'file',
                'file.uid = meta.file'
            )
            ->where(
                $queryBuilder->expr()->like(
                    'file.mime_type',
                    $queryBuilder->createNamedParameter('image/%')
                )
            )
            ->setMaxResults(20)
            ->orderBy('meta.tstamp', 'DESC');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('meta.alternative', $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->isNull('meta.alternative')
            )
        );

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        foreach ($results as $key => $row) {
            $fileObject = $resourceFactory->getFileObject((int)$row['file']);
            try {
                $fileObject->getParentFolder();
            } catch (\Throwable $th) {
                $fileObject->setMissing(true);
            }

            // Check if the current BE user can read the file
            if ($fileObject->getStorage()->checkFileActionPermission('read', $fileObject)) {
                $results[$key]['file'] = $fileObject;
            } else {
                unset($results[$key]);
            }
        }

        // Count missing alt texts
        $missingCountQueryBuilder = clone $queryBuilder;
        $missingCountQueryBuilder->count('meta.uid');
        $missingCountQueryBuilder->resetQueryPart('orderBy'); // Resets the limit
        $missingFieldCount = (int)$missingCountQueryBuilder->executeQuery()->fetchOne();

        // Count total image metadata records
        $totalCountQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $totalCount = (int)$totalCountQueryBuilder
            ->count('meta.uid')
            ->from('sys_file_metadata', 'meta')
            ->innerJoin(
                'meta',
                'sys_file',
                'file',
                'file.uid = meta.file'
            )
            ->where(
                $totalCountQueryBuilder->expr()->like(
                    'file.mime_type',
                    $totalCountQueryBuilder->createNamedParameter('image/%')
                )
            )
            ->executeQuery()
            ->fetchOne();

        return [
            'missingFieldCount' => $missingFieldCount,
            'totalCount' => $totalCount,
            'results' => $results,
        ];
    }
}
