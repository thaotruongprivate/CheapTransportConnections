<?php

namespace App\Service;

use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VirailRoutes
{
    private const URL = 'https://www.virail.com/virail/v7/search/en_us?from=c.3173435&to=c.3169070&lang=en_us&dt=%s&currency=USD&adult_passengers=1';
    private const EXCLUDED_TRANSPORTS = ['car'];
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param DateTime $date
     * @return array|null
     */
    public function getDayCheapestRoute(DateTime $date) {

        $routes = $this->getAllRoutes($date);
        if (!$routes) {
            return null;
        }
        $cheapestRoute = $this->getCheapestRoute($routes);
        $departureTime = $this->getDepartureTime($cheapestRoute);
        $arrivalTime = $this->getArrivalTime($cheapestRoute);
        $dayDifference = $this->getDifferenceInDays($departureTime, $arrivalTime);
        $departureStation = $this->getDepartureStation($cheapestRoute);
        $arrivalStation = $this->getArrivalStation($cheapestRoute);

        return [
            'date' => $departureTime->format('Y-m-d'),
            'transport' => $cheapestRoute['transport'],
            'departure' => "{$departureStation} ({$departureTime->format('H:i')})",
            'arrival' => "{$arrivalStation} ({$arrivalTime->format('H:i')})" .
                ($dayDifference ? " +{$dayDifference}" : ''),
            'duration' => $cheapestRoute['duration'],
            'price' => $cheapestRoute['price']
        ];
    }

    /**
     * @param DateTime $date
     * @return array|null
     */
    private function getAllRoutes(DateTime $date) {
        try {
            $url = sprintf(self::URL, $date->format('Y-m-d'));
            $result = $this->httpClient->request('GET', $url);
            if ($result->getStatusCode() === Response::HTTP_OK) {
                $routes = json_decode($result->getContent(), true)['result'];
                $filteredRoutes = array_filter($routes, function ($route){
                    return !in_array($route['transport'], self::EXCLUDED_TRANSPORTS);
                });
                return array_values($filteredRoutes);
            }
        } catch (TransportExceptionInterface $e) {
        } catch (ClientExceptionInterface $e) {
        } catch (RedirectionExceptionInterface $e) {
        } catch (ServerExceptionInterface $e) {
        }
        return null;
    }

    private function getDifferenceInDays(DateTime $startTime, DateTime $endTime) : int {
        try {
            $endDate = (new DateTime($endTime->format('Y-m-d')));
            $startDate = (new DateTime($startTime->format('Y-m-d')));
            return $startDate->diff($endDate)->d;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getCheapestRoute(array $routes): array {
        $cheapestPrice = $routes[0]['priceVal'];
        $key = 0;
        foreach ($routes as $index => $route) {
            if ($route['priceVal'] < $cheapestPrice) {
                $cheapestPrice = $route['priceVal'];
                $key = $index;
            }
        }
        return $routes[$key];
    }

    private function getDepartureTime(array $route) : DateTime {
        return (new DateTime())->setTimestamp($this->getDepartureSegment($route)['fromTimeVal']);
    }

    private function getArrivalTime(array $route) : DateTime {
        return (new DateTime())->setTimestamp($this->getArrivalSegment($route)['toTimeVal']);
    }

    private function getDepartureStation(array $route) : string {
        return $this->getDepartureSegment($route)['departure'];
    }

    private function getArrivalStation(array $route) : string {
        return $this->getArrivalSegment($route)['arrival'];
    }

    private function getArrivalSegment(array $route) : array {
        $arrivalTime = $route['segments'][0]['toTimeVal'];
        $index = 0;
        foreach ($route['segments'] as $key => $segment) {
            if ($segment['toTimeVal'] > $arrivalTime) {
                $arrivalTime = $segment['toTimeVal'];
                $index = $key;
            }
        }

        return $route['segments'][$index];
    }

    private function getDepartureSegment(array $route) : array {
        $departureTime = $route['segments'][0]['fromTimeVal'];
        $index = 0;
        foreach ($route['segments'] as $key => $segment) {
            if ($segment['fromTimeVal'] < $departureTime) {
                $departureTime = $segment['fromTimeVal'];
                $index = $key;
            }
        }
        return $route['segments'][$index];
    }
}