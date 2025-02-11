<?php

namespace App\Controller;

use stdClass;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class CsvUpload extends AbstractController
{
    public function __construct(public Connection $db) {}

    #[Route('/csv-upload', name: 'csv-upload')]
    public function handleRequest(Request $request): Response
    {

        if ($request->isMethod('POST')) {
            return $this->save($request);
        }



        return $this->render('csv-upload.html.twig');
    }

    public function save(Request $request)
    {

        $option = $request->request->get('submit');

        switch ($option) {
            case 'upload':
                $uploadedFile = $request->files->get('csvFile');
                $file = new stdClass();
                $file->pathName = $uploadedFile->getPathname();
                $file->originalName = $uploadedFile->getClientOriginalName();
                $file->mimeType = $uploadedFile->getClientMimeType();
                break;
            case 'template':
                $file = new stdClass();
                $file->pathName = $_SERVER['DOCUMENT_ROOT'] . "/../private/data/customers.csv";
                $file->originalName = basename($file->pathName);
                $file->mimeType = mime_content_type($file->pathName);
                break;
        }

        if (!$file) {
            throw new CsvUpload\Exception\UploadFailure('No file uploaded.');
        }

        if ($file->mimeType !== 'text/csv') {
            throw new CsvUpload\Exception\UploadFailure("MIME type {$file->mimeType} not supported.");
        }

        if (!$file->originalName) {
            throw new CsvUpload\Exception\UploadFailure('No file name provided.');
        }

        if (!file_exists($file->pathName)) {
            throw new CsvUpload\Exception\UploadFailure('File does not exist.');
        }

        $this->db->beginTransaction();

        $csvUploadId = $this->commitCsvUpload($file->originalName);

        if (!$csvUploadId) {
            throw new CsvUpload\Exception\UploadFailure('Failed to insert record.');
        }

        // Loop though CSV file and insert into database
        $handle = fopen($file->pathName, 'r');

        if (!$handle) {
            throw new CsvUpload\Exception\UploadFailure('Failed to open file.');
        }

        $hasHeaderRow = (bool) $request->request->get('headerRow');

        if ($hasHeaderRow) {

            $header = fgetcsv($handle);

            if (!$header) {
                throw new CsvUpload\Exception\UploadFailure('Failed to read header.');
            }

            $this->commitCsvUploadColumns($csvUploadId, $header);
        }

        $rowIndex = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $this->commitCsvUploadRow($csvUploadId, $rowIndex++, $row);
        }

        fclose($handle);

        $this->db->commit();

        return $this->redirectToRoute('csv-view', ['csvUploadId' => $csvUploadId]);
    }

    public function commitCsvUpload(string $originalName): int
    {
        static $statement = null;

        $statement ??= $this->db->prepare(<<<SQL
            INSERT INTO CsvUpload
            (fileName)
            VALUES
            (:fileName)
        SQL);

        $statement->bindValue('fileName', $originalName);

        $statement->executeStatement();

        return $this->db->lastInsertId();
    }

    public function commitCsvUploadColumns(int $csvUploadId, array $columns)
    {
        static $statement = null;

        $statement ??= $this->db->prepare(<<<SQL
            INSERT INTO CsvUploadColumn
            (csvUploadId, columnIndex, name)
            VALUES
            (:csvUploadId, :columnIndex, :name)
        SQL);

        $statement->bindValue('csvUploadId', $csvUploadId);

        foreach ($columns as $columnIndex => $name) {

            $statement->bindValue('columnIndex', $columnIndex);
            $statement->bindValue('name', $name);

            $statement->executeStatement();
        }
    }

    public function commitCsvUploadRow(int $csvUploadId, int $rowIndex, array $row)
    {
        static $statement = null;

        $statement ??= $this->db->prepare(<<<SQL
            INSERT INTO CsvUploadCell
            (csvUploadId, rowIndex, columnIndex, value)
            VALUES
            (:csvUploadId, :rowIndex, :columnIndex, :value)
        SQL);

        $statement->bindValue('csvUploadId', $csvUploadId);
        $statement->bindValue('rowIndex', $rowIndex);

        foreach ($row as $columnIndex => $value) {
            $statement->bindValue('columnIndex', $columnIndex);
            $statement->bindValue('value', $value);
            $statement->executeStatement();
        }
    }
}
