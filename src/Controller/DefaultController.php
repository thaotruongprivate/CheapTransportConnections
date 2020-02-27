<?php

namespace App\Controller;

use App\Service\VirailTransport;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    public function index(Request $request, VirailTransport $transport)
    {
        $routes = [];
        $excludedTransport = $request->get('exclude') ? explode(',', $request->get('exclude')) : [];
        for ($i = 1; $i <= 7; $i++) {
            $date = new DateTime("+{$i} day");
            $dayCheapestRoute = $transport->getDayCheapestConnection($date, $excludedTransport);
            if ($dayCheapestRoute) {
                $routes[] = $dayCheapestRoute;
            }
        }

        return $this->render('transportation/index.html.twig', [
            'title' => 'Cheapest routes',
            'routes' => $routes
        ]);
    }
}