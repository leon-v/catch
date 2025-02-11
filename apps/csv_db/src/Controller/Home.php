<?php
/**
 * Home router controller file
 */
namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Home router controller class
 */
class Home extends AbstractController
{
    public function __construct(public Connection $db) {}

    /**
     * Home Page load request handler
     */
    #[Route('/', name: 'home')]
    public function upload(Request $request): Response {
        return $this->render('home.html.twig');
    }
}