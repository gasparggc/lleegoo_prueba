<?php

namespace App\Application\Port;

use App\Domain\Entity\Segment;

interface FlightPort
{
    public function fetchFlights(string $origin, string $destination, string $date): array;
}
