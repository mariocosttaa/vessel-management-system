<?php

namespace App\Console\Commands\UserManage\Handlers;

use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselUserRole;
use App\Models\Maintenance;
use Illuminate\Support\Facades\DB;

class VesselManagementHandler extends BaseHandler
{
    public function createVessel(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Create Vessel');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $name = $this->ask('Vessel name');
        if (empty($name)) {
            $this->error('Vessel name is required.');
            return;
        }

        $registrationNumber = $this->ask('Registration number');
        if (empty($registrationNumber)) {
            $this->error('Registration number is required.');
            return;
        }

        if (Vessel::where('registration_number', $registrationNumber)->exists()) {
            $this->error('A vessel with this registration number already exists.');
            return;
        }

        $vesselType = $this->choice('Vessel type', ['cargo', 'passenger', 'fishing', 'fish', 'yacht'], 'fishing');
        $status = $this->choice('Status', ['active', 'suspended', 'maintenance', 'inactive'], 'active');
        $capacity = $this->ask('Capacity (optional)', null);
        $yearBuilt = $this->ask('Year built (optional)', null);
        $notes = $this->ask('Notes (optional)', null);

        $ownerEmail = $this->ask('Owner email (optional - must be paid_system user)');
        $ownerId = null;
        if ($ownerEmail) {
            $owner = User::where('email', $ownerEmail)->where('user_type', 'paid_system')->first();
            if (!$owner) {
                $this->error('Owner not found or is not a paid_system user.');
                return;
            }
            $ownerId = $owner->id;
        }

        try {
            $vessel = Vessel::create([
                'name' => $name,
                'registration_number' => $registrationNumber,
                'vessel_type' => $vesselType,
                'status' => $status,
                'capacity' => $capacity ? (int) $capacity : null,
                'year_built' => $yearBuilt ? (int) $yearBuilt : null,
                'notes' => $notes,
                'owner_id' => $ownerId,
            ]);

            $this->info("✓ Vessel {$vessel->name} created successfully (ID: {$vessel->id}).");
        } catch (\Exception $e) {
            $this->error("✗ Error creating vessel: {$e->getMessage()}");
        }
    }

    public function listVessels(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           List Vessels');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. List all vessels');
        $this->line('  2. Search vessels');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->listAllVessels(),
            '2' => $this->searchVessels(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    private function listAllVessels(): void
    {
        $this->listVesselsWithPagination(
            Vessel::with('owner')
                ->withCount(['crewMembers', 'movimentations', 'mareas'])
                ->orderBy('created_at', 'desc'),
            'vessels'
        );
    }

    private function searchVessels(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Search Vessels');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $searchTerm = $this->ask('Enter search term (name or registration number)', '');
        if (empty($searchTerm)) {
            $this->error('Search term is required.');
            return;
        }

        $query = Vessel::with('owner')
            ->withCount(['crewMembers', 'movimentations', 'mareas'])
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('registration_number', 'like', "%{$searchTerm}%");
            })
            ->orderBy('created_at', 'desc');

        $this->listVesselsWithPagination($query, "vessels matching '{$searchTerm}'");
    }

    private function listVesselsWithPagination($query, string $label): void
    {
        $perPage = 15;
        $page = 1;
        $total = $query->count();

        if ($total === 0) {
            $this->info("No {$label} found.");
            return;
        }

        $totalPages = (int) ceil($total / $perPage);

        while (true) {
            $vessels = (clone $query)
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $headers = ['ID', 'Name', 'Registration', 'Type', 'Status', 'Owner', 'Crew', 'Mov.', 'Mareas', 'Maint.', 'Created', 'Updated'];
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
                    $vessel->created_at->format('Y-m-d H:i'),
                    $vessel->updated_at->format('Y-m-d H:i'),
                ];
            })->toArray();

            $this->newLine();
            $this->table($headers, $rows);
            $this->info("Page {$page} of {$totalPages} | Total: {$total} {$label}");

            if ($totalPages <= 1) {
                break;
            }

            $this->newLine();
            $this->line('Navigation:');
            if ($page > 1) {
                $this->line("  [p] Previous page");
            }
            if ($page < $totalPages) {
                $this->line("  [n] Next page");
            }
            $this->line("  [g] Go to page (1-{$totalPages})");
            $this->line("  [q] Quit");
            $this->newLine();

            $action = strtolower($this->ask('Action', 'q'));

            if ($action === 'q') {
                break;
            } elseif ($action === 'p' && $page > 1) {
                $page--;
            } elseif ($action === 'n' && $page < $totalPages) {
                $page++;
            } elseif ($action === 'g') {
                $targetPage = (int) $this->ask("Enter page number (1-{$totalPages})", $page);
                if ($targetPage >= 1 && $targetPage <= $totalPages) {
                    $page = $targetPage;
                } else {
                    $this->error("Invalid page number. Please enter a number between 1 and {$totalPages}.");
                }
            } else {
                $this->error('Invalid action. Please try again.');
            }
        }
    }

    public function viewVesselDetails(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $this->displayVesselInfo($vessel);
    }

    public function updateVessel(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $this->displayVesselInfo($vessel);
        $this->newLine();

        $this->line('What would you like to update?');
        $this->line('  1. Name');
        $this->line('  2. Registration number');
        $this->line('  3. Vessel type');
        $this->line('  4. Status');
        $this->line('  5. Capacity');
        $this->line('  6. Year built');
        $this->line('  7. Notes');
        $this->line('  8. Owner');
        $this->line('  0. Cancel');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');
        if ($choice === '0') {
            return;
        }

        try {
            match ($choice) {
                '1' => $vessel->update(['name' => $this->ask('New name', $vessel->name)]),
                '2' => $vessel->update(['registration_number' => $this->ask('New registration number', $vessel->registration_number)]),
                '3' => $vessel->update(['vessel_type' => $this->choice('Vessel type', ['cargo', 'passenger', 'fishing', 'fish', 'yacht'], $vessel->vessel_type)]),
                '4' => $vessel->update(['status' => $this->choice('Status', ['active', 'suspended', 'maintenance', 'inactive'], $vessel->status)]),
                '5' => $vessel->update(['capacity' => $this->ask('Capacity', $vessel->capacity) ? (int) $this->ask('Capacity', $vessel->capacity) : null]),
                '6' => $vessel->update(['year_built' => $this->ask('Year built', $vessel->year_built) ? (int) $this->ask('Year built', $vessel->year_built) : null]),
                '7' => $vessel->update(['notes' => $this->ask('Notes', $vessel->notes)]),
                '8' => $this->updateVesselOwner($vessel),
                default => $this->error('Invalid option.'),
            };

            if ($choice !== '8') {
                $this->info("✓ Vessel updated successfully.");
            }
        } catch (\Exception $e) {
            $this->error("✗ Error updating vessel: {$e->getMessage()}");
        }
    }

    public function deleteVessels(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Delete Vessels');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Delete a specific vessel');
        $this->line('  2. Delete multiple vessels by registration (comma-separated)');
        $this->line('  3. Delete vessels with no crew and no transactions');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');
        if ($choice === '0') {
            return;
        }

        match ($choice) {
            '1' => $this->deleteSingleVessel(),
            '2' => $this->deleteMultipleVessels(),
            '3' => $this->deleteEmptyVessels(),
            default => $this->error('Invalid option.'),
        };
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

    private function displayVesselInfo(Vessel $vessel): void
    {
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
    }

    private function updateVesselOwner(Vessel $vessel): void
    {
        $ownerEmail = $this->ask('Owner email (leave empty to remove owner)');

        if (empty($ownerEmail)) {
            $vessel->update(['owner_id' => null]);
            $this->info("✓ Owner removed from vessel.");
            return;
        }

        $owner = User::where('email', $ownerEmail)->where('user_type', 'paid_system')->first();
        if (!$owner) {
            $this->error('Owner not found or is not a paid_system user.');
            return;
        }

        $vessel->update(['owner_id' => $owner->id]);
        $this->info("✓ Owner updated to {$owner->email}.");
    }

    private function deleteSingleVessel(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $this->displayVesselInfo($vessel);
        $this->newLine();

        $crewMembers = $vessel->crewMembers()->count();
        $transactions = $vessel->movimentations()->count();
        $vesselRoles = VesselUserRole::where('vessel_id', $vessel->id)->count();
        $vesselUsers = \App\Models\VesselUser::where('vessel_id', $vessel->id)->count();

        $this->warn('⚠ Relationship Summary:');
        $this->line("  - Crew Members: {$crewMembers}");
        $this->line("  - Transactions: {$transactions}");
        $this->line("  - Vessel Roles: {$vesselRoles}");
        $this->line("  - Vessel Users: {$vesselUsers}");
        $this->newLine();

        if ($crewMembers > 0 || $transactions > 0) {
            $this->error('⚠ WARNING: This vessel has crew members or transactions. Deletion may cause data loss!');
            $this->newLine();
        }

        if (!$this->confirm('Are you sure you want to DELETE this vessel? This action cannot be undone!', false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        try {
            DB::beginTransaction();

            if ($vesselRoles > 0) {
                VesselUserRole::where('vessel_id', $vessel->id)->delete();
                $this->line("✓ Removed {$vesselRoles} vessel role(s)");
            }

            if ($vesselUsers > 0) {
                \App\Models\VesselUser::where('vessel_id', $vessel->id)->delete();
                $this->line("✓ Removed {$vesselUsers} vessel user relationship(s)");
            }

            $vessel->update(['owner_id' => null]);

            $vesselName = $vessel->name;
            $vessel->delete();

            DB::commit();
            $this->info("✓ Vessel {$vesselName} has been deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Error deleting vessel: {$e->getMessage()}");
        }
    }

    private function deleteMultipleVessels(): void
    {
        $registrationsInput = $this->ask('Enter vessel registration numbers (comma-separated)');
        if (empty($registrationsInput)) {
            $this->error('No registration numbers provided.');
            return;
        }

        $registrations = array_map('trim', explode(',', $registrationsInput));
        $vessels = Vessel::whereIn('registration_number', $registrations)->get();

        if ($vessels->isEmpty()) {
            $this->error('No vessels found with the provided registration numbers.');
            return;
        }

        $this->newLine();
        $this->info('Found ' . $vessels->count() . ' vessel(s):');
        foreach ($vessels as $vessel) {
            $this->line("  - {$vessel->name} ({$vessel->registration_number})");
        }
        $this->newLine();

        if (!$this->confirm('Are you sure you want to DELETE these vessels? This action cannot be undone!', false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        $deleted = 0;
        $errors = 0;

        foreach ($vessels as $vessel) {
            try {
                DB::beginTransaction();
                VesselUserRole::where('vessel_id', $vessel->id)->delete();
                \App\Models\VesselUser::where('vessel_id', $vessel->id)->delete();
                $vessel->update(['owner_id' => null]);
                $vessel->delete();
                DB::commit();
                $deleted++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Error deleting {$vessel->name}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("✓ Deleted {$deleted} vessel(s) successfully.");
        if ($errors > 0) {
            $this->warn("⚠ {$errors} vessel(s) could not be deleted.");
        }
    }

    private function deleteEmptyVessels(): void
    {
        $vessels = Vessel::whereDoesntHave('crewMembers')
            ->whereDoesntHave('movimentations')
            ->get();

        if ($vessels->isEmpty()) {
            $this->info('No empty vessels found.');
            return;
        }

        $this->info("Found {$vessels->count()} empty vessel(s):");
        foreach ($vessels->take(10) as $vessel) {
            $this->line("  - {$vessel->name} ({$vessel->registration_number})");
        }
        if ($vessels->count() > 10) {
            $this->line("  ... and " . ($vessels->count() - 10) . " more");
        }
        $this->newLine();

        if (!$this->confirm("Delete all {$vessels->count()} empty vessel(s)?", false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        $deleted = 0;
        foreach ($vessels as $vessel) {
            try {
                DB::beginTransaction();
                VesselUserRole::where('vessel_id', $vessel->id)->delete();
                \App\Models\VesselUser::where('vessel_id', $vessel->id)->delete();
                $vessel->update(['owner_id' => null]);
                $vessel->delete();
                DB::commit();
                $deleted++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Error deleting {$vessel->name}: {$e->getMessage()}");
            }
        }

        $this->info("✓ Deleted {$deleted} empty vessel(s) successfully.");
    }
}

