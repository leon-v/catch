<?php
namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class Csv
{
    public function __construct(public Connection $db) {}

    #[Route('/api/csv', name: 'csv-api')]
    public function fetchData(): JsonResponse
    {
        // Fetch your data here
        $data = [
            ['column1' => 'Value 1', 'column2' => 'Value 2', 'column3' => 'Value 3'],
            // Add more rows as needed
        ];

        return new JsonResponse($data);
    }
}