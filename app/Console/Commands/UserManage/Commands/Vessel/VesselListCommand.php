<?php

namespace App\Console\Commands\UserManage\Commands\Vessel;

use App\Models\Vessel;
use App\Models\Maintenance;
use Illuminate\Console\Command;

class VesselListCommand extends Command
{
    protected $signature = 'vessel:list
                            {--status= : Filter by status}
                            {--type= : Filter by vessel type}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List all vessels';

    public function handle(): int
    {
        $status = $this->option('status');
        $type = $this->option('type');
        $format = $this->option('format');

        $query = Vessel::with('owner')
            ->withCount(['crewMembers', 'movimentations', 'mareas']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('vessel_type', $type);
        }

        $vessels = $query->orderBy('created_at', 'desc')->get();

        if ($vessels->isEmpty()) {
            $this->info('No vessels found.');
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $vessels->map(function ($vessel) {
                return [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'registration_number' => $vessel->registration_number,
                    'vessel_type' => $vessel->vessel_type,
                    'status' => $vessel->status,
                    'owner' => $vessel->owner ? $vessel->owner->email : null,
                    'crew_count' => $vessel->crew_members_count,
                    'movimentations_count' => $vessel->movimentations_count,
                    'mareas_count' => $vessel->mareas_count,
                    'maintenances_count' => Maintenance::where('vessel_id', $vessel->id)->count(),
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Registration', 'Type', 'Status', 'Owner', 'Crew', 'Mov.', 'Mareas', 'Maint.'];
        $rows = $vessels->map(function ($vessel) {
            return [
                $vessel->id,
                $vessel->name,
                $vessel->registration_number,
                $vessel->vessel_type,
                $vessel->status,
                $vessel->owner ? $vessel->owner->email : 'None',
                $vessel->crew_members_count,
                $vessel->movimentations_count,
                $vessel->mareas_count,
                Maintenance::where('vessel_id', $vessel->id)->count(),
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$vessels->count()} vessel(s)");

        return Command::SUCCESS;
    }
}

