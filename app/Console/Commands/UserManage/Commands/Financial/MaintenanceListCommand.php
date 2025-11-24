<?php

namespace App\Console\Commands\UserManage\Commands\Financial;

use App\Models\Maintenance;
use App\Models\Vessel;
use Illuminate\Console\Command;

class MaintenanceListCommand extends Command
{
    protected $signature = 'maintenance:list
                            {vessel : Vessel ID or registration number}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List maintenances for a vessel';

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

        $maintenances = Maintenance::where('vessel_id', $vessel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($maintenances->isEmpty()) {
            $this->info("No maintenances found for {$vessel->name}.");
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $maintenances->map(function ($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'maintenance_number' => $maintenance->maintenance_number,
                    'name' => $maintenance->name,
                    'status' => $maintenance->status,
                    'start_date' => $maintenance->start_date ? $maintenance->start_date->format('Y-m-d') : null,
                    'end_date' => $maintenance->end_date ? $maintenance->end_date->format('Y-m-d') : null,
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Number', 'Name', 'Status', 'Start Date', 'End Date'];
        $rows = $maintenances->map(function ($maintenance) {
            return [
                $maintenance->id,
                $maintenance->maintenance_number,
                $maintenance->name,
                $maintenance->status,
                $maintenance->start_date ? $maintenance->start_date->format('Y-m-d') : 'N/A',
                $maintenance->end_date ? $maintenance->end_date->format('Y-m-d') : 'N/A',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$maintenances->count()} maintenance(s)");

        return Command::SUCCESS;
    }
}

