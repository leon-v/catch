<?php
/**
 * CSV API controller file
 */

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * CSV API controller
 */
class Csv extends AbstractController
{
    /**
     * Constructor
     *
     * @param Connection $db Database connection
     */
    public function __construct(public Connection $db) {}

    /**
     * API route to fetch all CSV entities
     */
    #[Route('/api/csv', name: 'csv-api-index')]
    public function fetchIndex(Request $request): JsonResponse
    {
        $statement = $this->db->prepare(<<<SQL
            SELECT *
            FROM CsvUpload
            ORDER BY CsvUploadId
        SQL);

        $result = $statement->executeQuery();

        /** @var array[object] $csvUploads */
        $csvUploads = [];

        // Return array of objects with
        while ($row = $result->fetchAssociative()) {

            $row = (object) $row;

            // Generate URL for each entity
            $row->uri = $this->generateUrl(
                'csv-api-entity',
                ['csvUploadId' => $row->csvUploadId],
                UrlGeneratorInterface::ABSOLUTE_PATH
            );

            $csvUploads[] = $row;
        }

        return new JsonResponse($csvUploads);
    }

    /**
     * API route to fetch a single CSV entity
     */
    #[Route('/api/csv/{csvUploadId}', name: 'csv-api-entity')]
    public function fetchData(Request $request): JsonResponse
    {
        // Get all requires values as the expected type
        $csvUploadId = (int) $request->attributes->get('csvUploadId');
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('perPage', 20);

        // Validate unsigned integer values

        if ($csvUploadId <= 0) {
            $exception = new Csv\Exception\InvalidInput('Invalid CSV upload ID passed');
            $exception->setStatusCode(404);
            throw $exception;
        }

        if ($page <= 0) {
            throw new Csv\Exception\InvalidInput('Invalid `page` query parameter passed');
        }

        if ($perPage <= 0) {
            throw new Csv\Exception\InvalidInput('Invalid `perPage` query parameter passed');
        }

        $page = $this->getCsvData($csvUploadId, $page, $perPage);

        return new JsonResponse($page);
    }

    /**
     * Fetch the CSV data that will be returned by the API
     */
    public function getCsvData(int $csvUploadId, int $page, int $perPage): object
    {
        // Fetch the CSV upload entity
        $csvUpload = $this->getCsvUpload($csvUploadId);

        if (!$csvUpload) {
            $exception = new Csv\Exception\InvalidInput("CSV upload ID '{$csvUploadId}' not found");
            $exception->setStatusCode(404);
            throw $exception;
        }

        // Fetch the columns for the CSV upload
        $csvUpload->columns = $this->getCsvUploadColumns($csvUpload->csvUploadId);

        // Filter data here if the table starts to collect sensitive data

        $csvUpload->rowCount = $this->getRowCount($csvUpload->csvUploadId);

        $csvUpload->pageCount = (int) ceil($csvUpload->rowCount / $perPage);

        $csvUpload->perPage  = $perPage;

        $csvUpload->page = $page;

        // Due to the way all cells are stored in one table, it's more optimal to use BETWEEN X AND Y
        // instead of LIMIT X, Y.
        // So calculate the from and to rows.
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

    /**
     * Fetch a single CSV upload entity
     */
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

        // 404 - No row found
        if ($row === false) {
            return null;
        }

        return (object) $row;
    }

    /**
     * Fetch the columns for a CSV upload
     */
    public function getCsvUploadColumns(int $csvUploadId): array
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

        // Map column index to column name
        // NOTE: json_encode() will convert a non-sequential array to an object
        // so this may need array_values(), but keep it as is to avoid header/column sync issues.
        return array_column($result->fetchAllAssociative(), 'name', 'columnIndex');
    }

    /**
     * Fetch the row count for a CSV upload
     */
    public function getRowCount(int $csvUploadId): int
    {

        // Since rowIndex has an index, this causes no load on the SQL server.
        // SQL EXPLAIN:
        // {
        //     "select_type": "SIMPLE",
        //     "table": null,
        //     "partitions": null,
        //     "type": null,
        //     "possible_keys": null,
        //     "key": null,
        //     "key_len": null,
        //     "ref": null,
        //     "rows": null,
        //     "filtered": null,
        //     "Extra": "Select tables optimized away"
        // }
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

    /**
     * Fetch the CSV upload cells for the given range.
     */
    public function getCsvUploadCells(int $csvUploadId, int $fromRow, int $toRow)
    {
        // Optimised, SQL EXPLAIN:
        /*
        {
            "select_type": "SIMPLE",
            "type": "range",
            "possible_keys": "csvUploadId_rowIndex_columnIndex",
            "key": "csvUploadId_rowIndex_columnIndex",
            "key_len": "12",
            "ref": null,
            "rows": 4010,
            "filtered": 100.00,
            "Extra": "Using index condition"
        }
        */
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
