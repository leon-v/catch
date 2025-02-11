<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Csv extends AbstractController
{
    public function __construct(public Connection $db) {}

    #[Route('/api/csv', name: 'csv-api-index')]
    public function fetchIndex(Request $request): JsonResponse
    {
        $statement = $this->db->prepare(<<<SQL
            SELECT *
            FROM CsvUpload
            ORDER BY CsvUploadId
        SQL);

        $result = $statement->executeQuery();

        $csvUploads = [];
        while ($row = $result->fetchAssociative()) {
            $row = (object) $row;
            $row->uri = $this->generateUrl(
                'csv-api-entity',
                ['csvUploadId' => $row->csvUploadId],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );
            $csvUploads[] = (object) $row;
        }

        return new JsonResponse($csvUploads);
    }

    #[Route('/api/csv/{csvUploadId}', name: 'csv-api-entity')]
    public function fetchData(Request $request): JsonResponse
    {
        $csvUploadId = (int) $request->attributes->get('csvUploadId');
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        if (!$csvUploadId) {
            $exception = new Csv\Exception\InvalidInput('Invalid CSV upload ID passed');
            $exception->setStatusCode(404);
            throw $exception;
        }

        if (!$page) {
            throw new Csv\Exception\InvalidInput('Invalid `page` query parameter passed');
        }

        if (!$perPage) {
            throw new Csv\Exception\InvalidInput('Invalid `perPage` query parameter passed');
        }

        $page = $this->getCsvData($csvUploadId, $page, $perPage);

        return new JsonResponse($page);
    }

    public function getCsvData(int $csvUploadId, int $page, int $perPage)
    {

        $csvUpload = $this->getCsvUpload($csvUploadId);

        if (!$csvUpload) {
            $exception = new Csv\Exception\InvalidInput("CSV upload ID '{$csvUploadId}' not found");
            $exception->setStatusCode(404);
            throw $exception;
        }


        $csvUpload->columns = $this->getCsvUploadColumns($csvUpload->csvUploadId);

        // Filter data here if the table starts to collect sensitive data

        $csvUpload->rowCount = $this->getRowCount($csvUpload->csvUploadId);

        $csvUpload->pageCount = (int) ceil($csvUpload->rowCount / $perPage);

        $csvUpload->perPage  = $perPage;

        $csvUpload->page = $page;

        $fromRow = ($page - 1) * $perPage;
        $toRow = $fromRow + $perPage - 1;

        $csvUpload->rows = [];

        $rows = $this->getCsvUploadCells($csvUpload->csvUploadId, $fromRow, $toRow);

        foreach ($rows as $row) {
            $csvUpload->rows[$row->rowIndex][$row->columnIndex] = $row->value;
        }

        $csvUpload->rows = array_values($csvUpload->rows);

        return $csvUpload;
    }

    public function getCsvUpload(int $csvUploadId): ?object
    {

        static $statement = null;
        $statement ??= $this->db->prepare(<<<SQL
            SELECT *
            FROM CsvUpload
            WHERE csvUploadId = :csvUploadId
        SQL);


        $statement->bindValue('csvUploadId', $csvUploadId);

        $result = $statement->executeQuery();

        $row = $result->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return (object) $row;
    }

    public function getCsvUploadColumns(int $csvUploadId)
    {

        static $statement = null;
        $statement ??= $this->db->prepare(<<<SQL
            SELECT columnIndex, name
            FROM CsvUploadColumn
            WHERE csvUploadId = :csvUploadId
            ORDER BY columnIndex
        SQL);

        $statement->bindValue('csvUploadId', $csvUploadId);

        $result = $statement->executeQuery();

        return array_column($result->fetchAllAssociative(), 'name', 'columnIndex');
    }

    public function getRowCount(int $csvUploadId): int
    {

        static $statement = null;
        $statement ??= $this->db->prepare(<<<SQL
            SELECT MAX(rowIndex)
            FROM CsvUploadCell
            WHERE csvUploadId = :csvUploadId
        SQL);

        $statement->bindValue('csvUploadId', $csvUploadId);

        $result = $statement->executeQuery();

        return $result->fetchOne() ?? 0;
    }

    public function getCsvUploadCells(int $csvUploadId, int $fromRow, int $toRow)
    {

        static $statement = null;
        $statement ??= $this->db->prepare(<<<SQL
            SELECT rowIndex, columnIndex, value
            FROM CsvUploadCell
            WHERE csvUploadId = :csvUploadId
            AND rowIndex BETWEEN :rowIndexMin AND :rowIndexMax
            ORDER BY rowIndex, columnIndex
        SQL);

        $statement->bindValue('csvUploadId', $csvUploadId);
        $statement->bindValue('rowIndexMin', $fromRow);
        $statement->bindValue('rowIndexMax', $toRow);

        $result = $statement->executeQuery();

        while ($row = $result->fetchAssociative()) {
            yield (object) $row;
        }
    }
}
