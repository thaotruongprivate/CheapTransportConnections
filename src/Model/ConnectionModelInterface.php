<?php


namespace App\Model;

use DateTime;

interface ConnectionModelInterface
{
    public function getDepartureStation(): string;
    public function getArrivalStation(): string;
    public function getDepartureTime(): DateTime;
    public function getArrivalTime(): DateTime;
    public function getDuration(): string;
    public function getTransport(): string;
    public function getPrice(): string;
}