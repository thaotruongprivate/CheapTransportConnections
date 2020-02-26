<?php

namespace App\Controller;

use App\Service\VirailRoutes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    public function index(VirailRoutes $virailRoutesService)
    {
        $routes = [];
        for ($i = 1; $i <= 7; $i++) {
            $dayCheapestRoute = $virailRoutesService->getDayCheapestRoute((new \DateTime("+{$i} day")));
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