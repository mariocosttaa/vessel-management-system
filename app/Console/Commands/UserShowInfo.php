<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserShowInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:show-info
                            {user : User ID or email}
                            {--detailed : Show detailed information including vessels and roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed information about a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $detailed = $this->option('detailed');

        // Find user by ID or email
        $user = is_numeric($userIdentifier)
            ? User::find($userIdentifier)
            : User::where('email', $userIdentifier)->first();

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            return Command::FAILURE;
        }

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("User Information");
        $this->info("═══════════════════════════════════════════════════════════");

        // Basic Information
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("User Type: {$user->user_type}");
        $this->line("Status: {$user->status}");
        $this->line("Login Permitted: " . ($user->login_permitted ? 'Yes' : 'No'));
        $this->line("Administrative: " . ($user->administrative ? 'Yes' : 'No'));
        $this->line("Email Verified: " . ($user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i:s') : 'No'));
        $this->line("Language: {$user->language}");

        // Crew Member Information (if applicable)
        if ($user->vessel_id) {
            $this->line("\nCrew Member Information:");
            $this->line("  Vessel ID: {$user->vessel_id}");
            $this->line("  Position ID: " . ($user->position_id ?? 'N/A'));
            $this->line("  Phone: " . ($user->phone ?? 'N/A'));
            $hireDate = $user->hire_date instanceof \Carbon\Carbon ? $user->hire_date->format('Y-m-d') : 'N/A';
            $this->line("  Hire Date: {$hireDate}");
        }

        // Vessel Ownership
        $ownedVessels = $user->ownedVessels()->count();
        $this->line("\nVessel Ownership:");
        $this->line("  Owned Vessels: {$ownedVessels}");

        if ($detailed && $ownedVessels > 0) {
            $this->line("\n  Owned Vessels List:");
            foreach ($user->ownedVessels as $vessel) {
                $this->line("    - {$vessel->name} ({$vessel->registration_number}) - Status: {$vessel->status}");
            }
        }

        // Vessel Access (through roles)
        $vesselAccess = $user->vesselUserRoles()->where('is_active', true)->count();
        $this->line("\nVessel Access:");
        $this->line("  Vessels with Access: {$vesselAccess}");

        if ($detailed && $vesselAccess > 0) {
            $this->line("\n  Vessel Access Details:");
            foreach ($user->vesselUserRoles()->where('is_active', true)->with(['vessel', 'vesselRoleAccess'])->get() as $vesselUserRole) {
                $vessel = $vesselUserRole->vessel;
                $role = $vesselUserRole->vesselRoleAccess;
                $this->line("    - {$vessel->name} ({$vessel->registration_number})");
                $this->line("      Role: {$role->display_name} ({$role->name})");
            }
        }

        // Account Dates
        $this->line("\nAccount Dates:");
        $this->line("  Created: {$user->created_at->format('Y-m-d H:i:s')}");
        $this->line("  Updated: {$user->updated_at->format('Y-m-d H:i:s')}");

        if ($user->invitation_sent_at) {
            $this->line("  Invitation Sent: {$user->invitation_sent_at->format('Y-m-d H:i:s')}");
        }
        if ($user->invitation_accepted_at) {
            $this->line("  Invitation Accepted: {$user->invitation_accepted_at->format('Y-m-d H:i:s')}");
        }

        // Permissions Summary
        $this->line("\nPermissions Summary:");
        $this->line("  Can Create Vessels: " . ($user->canCreateVessels() ? 'Yes' : 'No'));
        $this->line("  Has Existing Account: " . ($user->hasExistingAccount() ? 'Yes' : 'No'));
        $this->line("  Is Crew Member: " . ($user->isCrewMember() ? 'Yes' : 'No'));

        $this->info("═══════════════════════════════════════════════════════════");

        return Command::SUCCESS;
    }
}

