<?php
/**
 * CSV View router controller file
 */

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * CSV View router controller class
 */
class CsvView extends AbstractController
{
    public function __construct(public Connection $db) {}

    /**
     * Basic request handler to load the template with the passed ID.
     */
    #[Route('/csv-view/{csvUploadId?}', name: 'csv-view')]
    public function handleRequest(Request $request): Response
    {
        $csvUploadId = $request->get('csvUploadId');

        $csvUploadId ??= 1; // Should probably be an error condition instead of using 1

        return $this->render('csv-view.html.twig', [
            'csvUploadId' => $csvUploadId,
        ]);
    }
}