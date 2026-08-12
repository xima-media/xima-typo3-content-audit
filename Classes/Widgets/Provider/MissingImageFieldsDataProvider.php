<?php

declare(strict_types=1);

namespace Xima\XimaTypo3ContentAudit\Widgets\Provider;

use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class MissingImageFieldsDataProvider implements ListDataProviderInterface
{
    private const DISPLAY_LIMIT = 20;

    protected string $missingField = 'alternative';
    public function __construct(private readonly \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool, private readonly \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory)
    {
    }

    /**
    * @param string $missingField
    */
    public function setMissingField(string $missingField): void
    {
        $this->missingField = $missingField;
    }

    /**
    * @throws \Doctrine\DBAL\Exception
    */
    public function getItems(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder
            ->select(
                'meta.uid',
                'meta.title',
                'meta.sys_language_uid',
                'meta.tstamp',
                'meta.crdate',
                'meta.' . $this->missingField,
                'meta.file', // reference to sys_file
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
            ->orderBy('meta.tstamp', 'DESC')
            ->addOrderBy('meta.uid', 'ASC');

        $queryBuilder->andWhere(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('meta.' . $this->missingField, $queryBuilder->createNamedParameter('')),
                $queryBuilder->expr()->isNull('meta.' . $this->missingField)
            )
        );

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Check if the current BE user can read the file
        $results = [];
        foreach ($rows as $row) {
            $fileObject = $this->resourceFactory->getFileObject((int)$row['file']);
            try {
                $fileObject->getParentFolder();
            } catch (\Throwable $th) {
                $fileObject->setMissing(true);
            }

            if (!$fileObject->getStorage()->checkFileActionPermission('read', $fileObject)) {
                continue;
            }

            $row['file'] = $fileObject;
            $results[] = $row;
            if (count($results) >= self::DISPLAY_LIMIT) {
                break;
            }
        }

        // Count missing alt texts
        $missingCountQueryBuilder = clone $queryBuilder;
        $missingCountQueryBuilder->count('meta.uid');
        // @todo When dropping support for TYPO3 12 we may use ->resetOrderBy() instead
        $matchCount = (int)$missingCountQueryBuilder->executeQuery()->fetchOne();

        // Count total image metadata records
        $totalCountQueryBuilder = $this->connectionPool
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
            'matchCount' => $matchCount,
            'totalCount' => $totalCount,
            'results' => $results,
        ];
    }
}
