<?php


namespace App\Service;


use App\Model\ConnectionModelInterface;
use App\Model\VirailConnectionModel;

class ConnectionFactory
{
    public function createConnection(array $connectionData): ConnectionModelInterface
    {
        return new VirailConnectionModel($connectionData);
    }
}