<?php

namespace App\Service;

use App\Model\ConnectionModelInterface;
use App\Model\VirailConnectionModel;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VirailTransport
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
    public function getDayCheapestConnection(DateTime $date, array $excludedTransport = []) {

        $connections = $this->getAllConnections($date, $excludedTransport);
        if (!$connections) {
            return null;
        }
        $cheapestConnection = $this->getCheapestConnection($connections);
        $departureTime = $cheapestConnection->getDepartureTime();
        $arrivalTime = $cheapestConnection->getArrivalTime();
        $dayDifference = $this->getDifferenceInDays($departureTime, $arrivalTime);
        $departureStation = $cheapestConnection->getDepartureStation();
        $arrivalStation = $cheapestConnection->getArrivalStation();

        return [
            'date' => $departureTime->format(self::DATE_FORMAT),
            'transport' => $cheapestConnection->getTransport(),
            'departure' => "{$departureStation} ({$departureTime->format(self::TIME_FORMAT)})",
            'arrival' => "{$arrivalStation} ({$arrivalTime->format(self::TIME_FORMAT)})" .
                ($dayDifference ? " +{$dayDifference}" : ''),
            'duration' => $cheapestConnection->getDuration(),
            'price' => $cheapestConnection->getPrice()
        ];
    }

    public function getAllConnections(DateTime $date, array $excludedTransport = []): array {
        try {
            $url = sprintf(self::URL, $date->format(self::DATE_FORMAT));
            $result = $this->httpClient->request('GET', $url);
            if ($result->getStatusCode() === Response::HTTP_OK) {
                $connections = json_decode($result->getContent(), true)['result'];
                $filteredConnections = array_filter($connections, function ($connection) use ($excludedTransport) {
                    return !in_array($connection['transport'], $excludedTransport);
                });
                return array_values($filteredConnections);
            }
        } catch (TransportExceptionInterface $e) {
        } catch (ClientExceptionInterface $e) {
        } catch (RedirectionExceptionInterface $e) {
        } catch (ServerExceptionInterface $e) {
        }
        return [];
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

    private function getCheapestConnection(array $connections) : ConnectionModelInterface {
        usort($connections, function ($a, $b) {
            return $a['priceVal'] <=> $b['priceVal'];
        });

        return new VirailConnectionModel($connections[0]);
    }
}