<?php

namespace App\Console\Commands\UserManage\Commands\Crew;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class CrewListCommand extends Command
{
    protected $signature = 'crew:list
                            {vessel : Vessel ID or registration number}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List crew members for a vessel';

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

        $crewMembers = User::where('vessel_id', $vessel->id)
            ->with('position')
            ->orderBy('name')
            ->get();

        if ($crewMembers->isEmpty()) {
            $this->info("No crew members found for {$vessel->name}.");
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $crewMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'position' => $member->position ? $member->position->name : null,
                    'status' => $member->status,
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $headers = ['ID', 'Name', 'Email', 'Position', 'Status'];
        $rows = $crewMembers->map(function ($member) {
            return [
                $member->id,
                $member->name,
                $member->email,
                $member->position ? $member->position->name : 'N/A',
                $member->status,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$crewMembers->count()} crew member(s)");

        return Command::SUCCESS;
    }
}

