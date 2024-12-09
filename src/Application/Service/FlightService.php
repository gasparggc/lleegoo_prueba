<?php

namespace App\Application\Service;

use App\Domain\Entity\Segment;
use App\Application\Port\FlightPort;


class FlightService
{
    private FlightPort $FlightPort;

    public function __construct(FlightPort $FlightPort)
    {
        $this->FlightPort = $FlightPort;
    }

    public function getAvailableFlights(string $origin, string $destination, string $date): array
    {
        return $this->FlightPort->fetchFlights($origin, $destination, $date);
    }
}
