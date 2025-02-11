<?php
/**
 * CSV Upload router controller file
 */

namespace App\Controller;

use stdClass;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * CSV Upload router controller class
 */
class CsvUpload extends AbstractController
{
    public function __construct(public Connection $db) {}

    /**
     * CSV upload Page load request handler
     */
    #[Route('/csv-upload', name: 'csv-upload')]
    public function handleRequest(Request $request): Response
    {

        // Save the file if it is a POST request
        if ($request->isMethod('POST')) {
            return $this->save($request);
        }

        return $this->render('csv-upload.html.twig');
    }

    /**
     * Save the uploaded CSV file to the DB
     */
    public function save(Request $request): Response
    {

        // Check which button was used to submit the form
        $option = $request->request->get('submit');

        switch ($option) {
            // Upload button was clicked, use the uploaded file
            case 'upload':
                $uploadedFile = $request->files->get('csvFile');
                $file = new stdClass();
                $file->pathName = $uploadedFile->getPathname();
                $file->originalName = $uploadedFile->getClientOriginalName();
                $file->mimeType = $uploadedFile->getClientMimeType();
                break;

            // Template button was clicked, use the template file
            case 'template':
                $file = new stdClass();
                $file->pathName = $_SERVER['DOCUMENT_ROOT'] . "/../private/data/customers.csv";
                $file->originalName = basename($file->pathName);
                $file->mimeType = mime_content_type($file->pathName);
                $hasHeaderRow = true;
                break;
        }

        // Validation

        if (!$file) {
            throw new CsvUpload\Exception\UploadFailure('No file uploaded.');
        }

        // We may need more MIME types added here as different OSes use different values.
        if ($file->mimeType !== 'text/csv') {
            throw new CsvUpload\Exception\UploadFailure("MIME type {$file->mimeType} not supported.");
        }

        if (!$file->originalName) {
            throw new CsvUpload\Exception\UploadFailure('No file name provided.');
        }

        if (!file_exists($file->pathName)) {
            throw new CsvUpload\Exception\UploadFailure('File does not exist.');
        }

        // To prevent SQL server disk IO, start a transaction to record the state to memory first.
        // If this gets slow, look at batching multiple inserts into a single query
        // and also look at committing more frequently on a timer.
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

        // Check if the header option was selected
        $hasHeaderRow ??= (bool) $request->request->get('headerRow');
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

        // Commit to disk once.
        // If this gets slow, look at batching multiple inserts into a single query
        // and also look at committing more frequently on a timer.
        $this->db->commit();

        fclose($handle);

        // Delete the temp file
        if ($option === 'upload') {
            unlink($file->pathName);
        }

        return $this->redirectToRoute('csv-view', ['csvUploadId' => $csvUploadId]);
    }

    /**
     * Commit CSV upload event to the database
     *
     * @return int The ID of the new record
     */
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

    /**
     * Commit CSV columns to the database
     */
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

    /**
     * Commit CSV upload rows to the database
     */
    public function commitCsvUploadRow(int $csvUploadId, int $rowIndex, array $row)
    {
        static $statement = null;
        // If this gets slow, look at batching multiple inserts into a single query
        // and also look at committing more frequently on a timer.
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
