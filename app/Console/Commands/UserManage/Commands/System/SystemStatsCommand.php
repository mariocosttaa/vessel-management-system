<?php

namespace App\Console\Commands\UserManage\Commands\System;

use App\Models\User;
use App\Models\Vessel;
use App\Models\Movimentation;
use App\Models\Marea;
use App\Models\Maintenance;
use App\Models\Supplier;
use Illuminate\Console\Command;

class SystemStatsCommand extends Command
{
    protected $signature = 'system:stats
                            {--format=table : Output format (table, json)}';

    protected $description = 'Display system statistics';

    public function handle(): int
    {
        $format = $this->option('format');

        $stats = [
            'users' => [
                'total' => User::count(),
                'paid' => User::where('user_type', 'paid_system')->count(),
                'unpaid' => User::where('user_type', 'employee_of_vessel')->count(),
            ],
            'vessels' => [
                'total' => Vessel::count(),
                'active' => Vessel::where('status', 'active')->count(),
            ],
            'crew' => [
                'total' => User::whereNotNull('vessel_id')->count(),
            ],
            'financial' => [
                'movimentations' => Movimentation::count(),
                'suppliers' => Supplier::count(),
            ],
            'operations' => [
                'mareas' => Marea::count(),
                'maintenances' => Maintenance::count(),
            ],
        ];

        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           System Statistics');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line("Total Users: {$stats['users']['total']}");
        $this->line("  - Paid Users: {$stats['users']['paid']}");
        $this->line("  - Unpaid Users: {$stats['users']['unpaid']}");
        $this->line("Total Vessels: {$stats['vessels']['total']}");
        $this->line("  - Active Vessels: {$stats['vessels']['active']}");
        $this->line("Total Crew Members: {$stats['crew']['total']}");
        $this->line("Total Movimentations: {$stats['financial']['movimentations']}");
        $this->line("Total Suppliers: {$stats['financial']['suppliers']}");
        $this->line("Total Mareas: {$stats['operations']['mareas']}");
        $this->line("Total Maintenances: {$stats['operations']['maintenances']}");
        $this->newLine();

        // Vessel owners statistics
        $vesselOwners = User::whereHas('ownedVessels')->withCount('ownedVessels')->get();
        if ($vesselOwners->isNotEmpty()) {
            $this->line("Vessel Owners:");
            foreach ($vesselOwners as $owner) {
                $this->line("  - {$owner->email}: {$owner->owned_vessels_count} vessel(s)");
            }
        }

        $this->info('═══════════════════════════════════════════════════════════');

        return Command::SUCCESS;
    }
}

