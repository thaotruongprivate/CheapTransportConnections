<?php

namespace App\Service;

use DateTime;

interface TransportInterface
{
    public function getAllRoutes(DateTime $dateTime, array $excludedTransport = []);
    public function getDayCheapestRoute(DateTime $date, array $excludedTransport = []);
}