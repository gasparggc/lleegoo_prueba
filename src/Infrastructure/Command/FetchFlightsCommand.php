<?php

namespace App\Infrastructure\Command;

use App\Application\Service\FlightService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchFlightsCommand extends Command
{
    protected static $defaultName = 'lleego:avail';

    private FlightService $flightService;

    public function __construct(FlightService $flightService)
    {
        parent::__construct('lleego:avail');
        $this->flightService = $flightService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fetch flights')
            ->addArgument('origin', InputArgument::REQUIRED, 'Origin IATA code')
            ->addArgument('destination', InputArgument::REQUIRED, 'Destination IATA code')
            ->addArgument('date', InputArgument::REQUIRED, 'Date of flight');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $origin = $input->getArgument('origin');
        $destination = $input->getArgument('destination');
        $date = $input->getArgument('date');

        $flights = $this->flightService->getAvailableFlights($origin, $destination, $date);

        $table = new Table($output);
        $table->setHeaders(['Origin Code','Origin Name','Destination Code','Destination Name', 'Start', 'End', 'Transport Number', 'Company Code', 'Company Name']);
        
        foreach ($flights as $flight) {
            $table->addRow([
                $flight->getOriginCode(),
                $flight->getOriginName(),
                $flight->getDestinationCode(),
                $flight->getDestinationName(),
                $flight->getStart()->format('Y-m-d H:i'),
                $flight->getEnd()->format('Y-m-d'),
                $flight->getTransportNumber(),
                $flight->getCompanyCode(),
                $flight->getCompanyName(),
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
