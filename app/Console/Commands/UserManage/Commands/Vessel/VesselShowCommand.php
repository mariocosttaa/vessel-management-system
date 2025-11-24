<?php

namespace App\Console\Commands\UserManage\Commands\Vessel;

use App\Models\Vessel;
use App\Models\Maintenance;
use Illuminate\Console\Command;

class VesselShowCommand extends Command
{
    protected $signature = 'vessel:show
                            {vessel : Vessel ID or registration number}';

    protected $description = 'Display detailed information about a vessel';

    public function handle(): int
    {
        $identifier = $this->argument('vessel');

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return Command::FAILURE;
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("Vessel Information: {$vessel->name}");
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("ID: {$vessel->id}");
        $this->line("Name: {$vessel->name}");
        $this->line("Registration: {$vessel->registration_number}");
        $this->line("Type: {$vessel->vessel_type}");
        $this->line("Status: {$vessel->status}");
        $this->line("Capacity: " . ($vessel->capacity ?? 'N/A'));
        $this->line("Year Built: " . ($vessel->year_built ?? 'N/A'));
        $this->line("Owner: " . ($vessel->owner ? $vessel->owner->email : 'None'));
        $this->line("Crew Members: " . $vessel->crewMembers()->count());
        $this->line("Movimentations: " . $vessel->movimentations()->count());
        $this->line("Mareas: " . $vessel->mareas()->count());
        $this->line("Maintenances: " . Maintenance::where('vessel_id', $vessel->id)->count());
        $this->line("Created: {$vessel->created_at->format('Y-m-d H:i:s')}");
        if ($vessel->notes) {
            $this->line("Notes: {$vessel->notes}");
        }
        $this->info('═══════════════════════════════════════════════════════════');

        return Command::SUCCESS;
    }
}

