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

    #[Route('/csv-view', name: 'csv-view')]
    public function handleRequest(Request $request): Response
    {
        return $this->render('csv-view.html.twig');
    }
}
