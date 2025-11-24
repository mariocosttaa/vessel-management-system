<?php

namespace App\Console\Commands\UserManage\Handlers;

use App\Models\User;
use App\Models\Vessel;
use App\Models\CrewPosition;

class CrewManagementHandler extends BaseHandler
{
    public function listCrewMembers(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $crewMembers = User::where('vessel_id', $vessel->id)
            ->with('position')
            ->orderBy('name')
            ->get();

        if ($crewMembers->isEmpty()) {
            $this->info("No crew members found for {$vessel->name}.");
            return;
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
    }

    public function assignCrewMember(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Assign/Remove Crew Member');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $this->line('Options:');
        $this->line('  1. Assign crew member to vessel');
        $this->line('  2. Remove crew member from vessel');
        $this->line('  0. Cancel');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->assignCrewToVessel($user, $vessel),
            '2' => $this->removeCrewFromVessel($user, $vessel),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    public function manageCrewPositions(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Crew Positions');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. List crew positions by vessel');
        $this->line('  2. Create crew position');
        $this->line('  3. Delete crew position');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->listCrewPositionsByVessel(),
            '2' => $this->createCrewPosition(),
            '3' => $this->deleteCrewPosition(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    private function selectUser(): ?User
    {
        $identifier = $this->ask('Enter user ID or email');
        if (empty($identifier)) {
            $this->error('User identifier is required.');
            return null;
        }

        $user = is_numeric($identifier)
            ? User::find($identifier)
            : User::where('email', $identifier)->first();

        if (!$user) {
            $this->error("User not found: {$identifier}");
            return null;
        }

        return $user;
    }

    private function selectVessel(): ?Vessel
    {
        $identifier = $this->ask('Enter vessel ID or registration number');
        if (empty($identifier)) {
            $this->error('Vessel identifier is required.');
            return null;
        }

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return null;
        }

        return $vessel;
    }

    private function assignCrewToVessel(User $user, Vessel $vessel): void
    {
        if ($user->vessel_id === $vessel->id) {
            $this->warn("User {$user->email} is already assigned to {$vessel->name}.");
            return;
        }

        if ($this->confirm("Assign {$user->email} to {$vessel->name}?", true)) {
            $user->update(['vessel_id' => $vessel->id]);
            $this->info("✓ User assigned to vessel successfully.");
        }
    }

    private function removeCrewFromVessel(User $user, Vessel $vessel): void
    {
        if (!$user->vessel_id || $user->vessel_id !== $vessel->id) {
            $this->warn("User {$user->email} is not assigned to {$vessel->name}.");
            return;
        }

        if ($this->confirm("Remove {$user->email} from {$vessel->name}?", false)) {
            $user->update(['vessel_id' => null]);
            $this->info("✓ User removed from vessel successfully.");
        }
    }

    private function listCrewPositionsByVessel(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $positions = CrewPosition::where('vessel_id', $vessel->id)
            ->orderBy('name')
            ->get();

        if ($positions->isEmpty()) {
            $this->info("No crew positions found for {$vessel->name}.");
            return;
        }

        $headers = ['ID', 'Name', 'Description'];
        $rows = $positions->map(function ($position) {
            return [
                $position->id,
                $position->name,
                $position->description ?? 'N/A',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$positions->count()} position(s)");
    }

    private function createCrewPosition(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $name = $this->ask('Position name');
        if (empty($name)) {
            $this->error('Position name is required.');
            return;
        }

        $description = $this->ask('Description (optional)');

        try {
            CrewPosition::create([
                'vessel_id' => $vessel->id,
                'name' => $name,
                'description' => $description,
            ]);

            $this->info("✓ Crew position created successfully.");
        } catch (\Exception $e) {
            $this->error("✗ Error creating crew position: {$e->getMessage()}");
        }
    }

    private function deleteCrewPosition(): void
    {
        $positionId = $this->ask('Enter position ID');
        if (empty($positionId) || !is_numeric($positionId)) {
            $this->error('Valid position ID is required.');
            return;
        }

        $position = CrewPosition::find($positionId);
        if (!$position) {
            $this->error('Position not found.');
            return;
        }

        if ($this->confirm("Delete position '{$position->name}'?", false)) {
            $position->delete();
            $this->info("✓ Position deleted successfully.");
        }
    }
}

