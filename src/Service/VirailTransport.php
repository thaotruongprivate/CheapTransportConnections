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

class VirailTransport implements TransportInterface
{
    private const URL = 'https://www.virail.com/virail/v7/search/en_us?from=c.3173435&to=c.3169070&lang=en_us&dt=%s&currency=USD&adult_passengers=1';
    private const DATE_FORMAT = 'Y-m-d';
    private const TIME_FORMAT = 'H:i';

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
     * @param array $excludedTransport
     * @return array|null
     */
    public function getDayCheapestRoute(DateTime $date, array $excludedTransport = []) {

        $routes = $this->getAllRoutes($date, $excludedTransport);
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
            'date' => $departureTime->format(self::DATE_FORMAT),
            'transport' => $cheapestRoute['transport'],
            'departure' => "{$departureStation} ({$departureTime->format(self::TIME_FORMAT)})",
            'arrival' => "{$arrivalStation} ({$arrivalTime->format(self::TIME_FORMAT)})" .
                ($dayDifference ? " +{$dayDifference}" : ''),
            'duration' => $cheapestRoute['duration'],
            'price' => $cheapestRoute['price']
        ];
    }

    /**
     * @param DateTime $date
     * @param array $excludedTransport
     * @return array|null
     */
    public function getAllRoutes(DateTime $date, array $excludedTransport = []) {
        try {
            $url = sprintf(self::URL, $date->format(self::DATE_FORMAT));
            $result = $this->httpClient->request('GET', $url);
            if ($result->getStatusCode() === Response::HTTP_OK) {
                $routes = json_decode($result->getContent(), true)['result'];
                $filteredRoutes = array_filter($routes, function ($route) use ($excludedTransport) {
                    return !in_array($route['transport'], $excludedTransport);
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
            $endDate = (new DateTime($endTime->format(self::DATE_FORMAT)));
            $startDate = (new DateTime($startTime->format(self::DATE_FORMAT)));
            return $startDate->diff($endDate)->d;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getCheapestRoute(array $routes): array {
        usort($routes, function ($a, $b) {
            return $a['priceVal'] <=> $b['priceVal'];
        });

        return $routes[0];
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
        usort($route['segments'], function ($a, $b) {
            return -($a['toTimeVal'] <=> $b['toTimeVal']);
        });

        return $route['segments'][0];
    }

    private function getDepartureSegment(array $route) : array {
        usort($route['segments'], function ($a, $b) {
            return $a['fromTimeVal'] <=> $b['fromTimeVal'];
        });

        return $route['segments'][0];
    }
}