<?php

namespace App\Model;

use DateTime;

class VirailConnectionModel implements ConnectionModelInterface
{
    protected $connectionData;

    public function __construct(array $connection)
    {
        $this->connectionData = $connection;
        $this->sortSegments();
    }

    public function getDuration(): string
    {
        return $this->connectionData['duration'];
    }

    public function getDepartureTime(): DateTime
    {
        return (new DateTime())->setTimestamp($this->getDepartureSegment()['fromTimeVal']);
    }

    public function getArrivalTime(): DateTime
    {
        return (new DateTime())->setTimestamp($this->getArrivalSegment()['toTimeVal']);
    }

    public function getDepartureStation(): string
    {
        return $this->getDepartureSegment()['departure'];
    }

    public function getArrivalStation(): string
    {
        return $this->getArrivalSegment()['arrival'];
    }

    private function getArrivalSegment(): array
    {
        $segmentCount = count($this->connectionData['segments']);
        return $this->connectionData['segments'][$segmentCount - 1];
    }

    private function getDepartureSegment(): array
    {
        return $this->connectionData['segments'][0];
    }

    public function getTransport(): string
    {
        return $this->connectionData['transport'];
    }

    public function getPrice(): string
    {
        return $this->connectionData['price'];
    }

    private function sortSegments(): void
    {
        usort($this->connectionData['segments'], function ($a, $b) {
            return $a['fromTimeVal'] <=> $b['fromTimeVal'];
        });
    }
}