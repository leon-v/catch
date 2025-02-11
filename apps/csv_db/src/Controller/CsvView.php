<?php
namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CsvView extends AbstractController
{
    public function __construct(public Connection $db) {}

    #[Route('/csv-view/{csvUploadId?}', name: 'csv-view')]
    public function handleRequest(Request $request): Response
    {
        $csvUploadId = $request->get('csvUploadId');

        $csvUploadId ??= 1;

        return $this->render('csv-view.html.twig', [
            'csvUploadId' => $csvUploadId,
        ]);
    }
}
