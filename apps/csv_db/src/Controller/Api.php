<?php
/**
 * Root API endpoint controller file
 *
 */

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Root API endpoint
 */
class Api
{
    public function __construct(public Connection $db) {}

    /**
     * Basic empty placeholder endpint
     */
    #[Route('/api', name: 'api')]
    public function handleRequest(Request $request): Response
    {
        return new JsonResponse([]);
    }
}
