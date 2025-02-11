<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class Api
{
    public function __construct(public Connection $db) {}

    #[Route('/api', name: 'api')]
    public function handleRequest(Request $request): Response
    {
        return new JsonResponse([]);
    }
}
