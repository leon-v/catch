<?php
namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Home extends AbstractController
{
    public function __construct(public Connection $db) {}

    #[Route('/', name: 'home')]
    public function upload(Request $request): Response {
        return $this->render('home.html.twig');
    }
}