<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Csv extends AbstractController
{
    public function __construct(public Connection $db) {}

    #[Route('/api/csv/{csvUploadId}', name: 'csv-api')]
    public function fetchData(Request $request): JsonResponse
    {
        $csvUploadId = (int) $request->attributes->get('csvUploadId');

        if (!is_numeric($csvUploadId)) {
            throw new \Exception('Invalid CSV upload ID');
        }

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('perPage', 20);

        $page = $this->getCsvData($csvUploadId, $page, $perPage);

        return new JsonResponse($page);
    }

    public function getCsvData(int $csvUploadId, int $page, int $perPage = 20)
    {

        $csvUpload = $this->getCsvUpload($csvUploadId);

        $csvUpload->columns = $this->getCsvUploadColumns($csvUpload->csvUploadId);

        $csvUpload->rowCount = $this->getRowCount($csvUpload->csvUploadId);

        $csvUpload->pageCount = (int) ceil($csvUpload->rowCount / $perPage);

        $csvUpload->perPage  = $perPage;

        $csvUpload->page = $page;

        $fromRow = ($page - 1) * $perPage;
        $toRow = $fromRow + $perPage - 1;

        $csvUpload->rows = [];

        $rows = $this->getCsvUploadCells($csvUpload->csvUploadId, $fromRow, $toRow);

        foreach ($rows as $row){
            $csvUpload->rows[$row->rowIndex][$row->columnIndex] = $row->value;
        }

        $csvUpload->rows = array_values($csvUpload->rows);

        return $csvUpload;
    }

    public function getCsvUpload(int $csvUploadId)
    {

        static $statement = null;
        $statement ??= $this->db->prepare(<<<SQL
            SELECT *
            FROM CsvUpload
            WHERE csvUploadId = :csvUploadId
        SQL);


        $statement->bindValue('csvUploadId', $csvUploadId);

        $result = $statement->executeQuery();

        return (object) $result->fetchAssociative();
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

        return $result->fetchOne();
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

        while ($row = $result->fetchAssociative()){
            yield (object) $row;
        }
    }
}
