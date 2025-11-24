<?php

namespace App\Console\Commands\UserManage\Commands\Financial;

use App\Models\Marea;
use App\Models\Vessel;
use Illuminate\Console\Command;

class MareaListCommand extends Command
{
    protected $signature = 'marea:list
                            {vessel : Vessel ID or registration number}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List mareas for a vessel';

    public function handle(): int
    {
        $identifier = $this->argument('vessel');
        $format = $this->option('format');

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return Command::FAILURE;
        }

        $mareas = Marea::where('vessel_id', $vessel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($mareas->isEmpty()) {
            $this->info("No mareas found for {$vessel->name}.");
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $mareas->map(function ($marea) {
                return [
                    'id' => $marea->id,
                    'marea_number' => $marea->marea_number,
                    'name' => $marea->name,
                    'status' => $marea->status,
                    'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                    'estimated_return_date' => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                    'actual_departure_date' => $marea->actual_departure_date ? $marea->actual_departure_date->format('Y-m-d') : null,
                    'actual_return_date' => $marea->actual_return_date ? $marea->actual_return_date->format('Y-m-d') : null,
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Number', 'Name', 'Status', 'Departure', 'Return'];
        $rows = $mareas->map(function ($marea) {
            $departure = $marea->actual_departure_date
                ? $marea->actual_departure_date->format('Y-m-d')
                : ($marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : 'N/A');
            $return = $marea->actual_return_date
                ? $marea->actual_return_date->format('Y-m-d')
                : ($marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : 'N/A');
            return [
                $marea->id,
                $marea->marea_number,
                $marea->name,
                $marea->status,
                $departure,
                $return,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$mareas->count()} marea(s)");

        return Command::SUCCESS;
    }
}

