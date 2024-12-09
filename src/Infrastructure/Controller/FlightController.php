<?php

namespace App\Infrastructure\Controller;

use App\Application\Service\FlightService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class FlightController
{
    private FlightService $flightService;

    public function __construct(FlightService $flightService)
    {
        $this->flightService = $flightService;
    }

    public function getFlights(Request $request): JsonResponse
    {
        $origin = $request->query->get('origin');
        $destination = $request->query->get('destination');
        $date = $request->query->get('date');

        if (!$origin || !$destination || !$date || !strtotime($date)) {
            return new JsonResponse(['error' => 'Invalid parameters'], 400);
        }

        $flights = $this->flightService->getAvailableFlights($origin, $destination, $date);

        if (empty($flights)) {
            return new JsonResponse([], 200);
        }

        $data = array_map(fn($flight) => [
            'originCode' => $flight->getOriginCode(),
            'originName' => $flight->getOriginName(),
            'destinationCode' => $flight->getDestinationCode(),
            'destinationName' => $flight->getDestinationName(),
            'start' => $flight->getStart()->format('Y-m-d H:i'),
            'end' => $flight->getEnd()->format('Y-m-d'),
            'transportNumber' => $flight->getTransportNumber(),
            'companyCode' => $flight->getCompanyCode(),
            'companyName' => $flight->getCompanyName(),
        ], $flights);

        return new JsonResponse($data);
    }
}
