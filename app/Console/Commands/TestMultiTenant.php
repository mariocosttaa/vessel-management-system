<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Vessel;

class TestMultiTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:multi-tenant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test multi-tenant functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚢 Testing Multi-Tenant Functionality...');
        $this->newLine();

        // Test user-vessel relationships
        $this->info('👥 User-Vessel Relationships:');
        $users = User::with('vessels')->get();

        foreach ($users as $user) {
            $this->line("  📧 {$user->name} ({$user->email})");
            foreach ($user->vessels as $vessel) {
                $role = $user->getRoleForVessel($vessel->id);
                $this->line("    🚢 {$vessel->name} - Role: {$role}");
            }
            $this->newLine();
        }

        // Test vessel access
        $this->info('🔐 Vessel Access Tests:');
        $vessel = Vessel::first();
        $user = User::first();

        if ($vessel && $user) {
            $this->line("  🚢 Vessel: {$vessel->name}");
            $this->line("  👤 User: {$user->name}");
            $this->line("  ✅ Has access: " . ($user->hasAccessToVessel($vessel->id) ? 'Yes' : 'No'));
            $this->line("  🎭 Role: " . ($user->getRoleForVessel($vessel->id) ?? 'None'));
            $this->newLine();
        }

        // Test vessel ownership
        $this->info('👑 Vessel Ownership:');
        $vessels = Vessel::with('owner')->get();
        foreach ($vessels as $vessel) {
            $owner = $vessel->owner ? $vessel->owner->name : 'No owner';
            $this->line("  🚢 {$vessel->name} - Owner: {$owner}");
        }
        $this->newLine();

        $this->info('✅ Multi-tenant test completed successfully!');

        return Command::SUCCESS;
    }
}

