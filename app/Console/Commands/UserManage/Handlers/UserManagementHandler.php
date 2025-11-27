<?php

namespace App\Console\Commands\UserManage\Handlers;

use App\Models\InvitationEmail;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselUser;
use App\Models\VesselUserRole;
use Illuminate\Support\Facades\DB;

class UserManagementHandler extends BaseHandler
{
    public function manageUserType(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage User Type');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Set user as Paid (paid_system)');
        $this->line('  2. Set user as Unpaid (employee_of_vessel)');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $subChoice = $this->ask('Select an option', '0');
        if ($subChoice === '0') {
            return;
        }

        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        match ($subChoice) {
            '1' => $this->setUserPaid($user),
            '2' => $this->setUserUnpaid($user),
            default => $this->error('Invalid option.'),
        };
    }

    public function manageLoginAccess(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Login Access');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Enable login for user');
        $this->line('  2. Disable login for user');
        $this->line('  3. Enable login for all users');
        $this->line('  4. Disable login for all users');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $subChoice = $this->ask('Select an option', '0');
        if ($subChoice === '0') {
            return;
        }

        if (in_array($subChoice, ['3', '4'])) {
            $this->handleBulkLoginAccess($subChoice);
            return;
        }

        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        match ($subChoice) {
            '1' => $this->enableUserLogin($user),
            '2' => $this->disableUserLogin($user),
            default => $this->error('Invalid option.'),
        };
    }

    public function manageUserStatus(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage User Status');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $this->line("Current status: {$user->status}");
        $this->newLine();
        $this->line('Available statuses:');
        $this->line('  1. active');
        $this->line('  2. inactive');
        $this->line('  3. on_leave');
        $this->line('  0. Cancel');
        $this->newLine();

        $choice = $this->ask('Select status', '0');
        $statusMap = ['1' => 'active', '2' => 'inactive', '3' => 'on_leave'];

        if ($choice === '0' || !isset($statusMap[$choice])) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->setUserStatus($user, $statusMap[$choice]);
    }

    public function manageAdministrative(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Administrative Privileges');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $current = $user->administrative ? 'Yes' : 'No';
        $this->line("Current administrative privileges: {$current}");
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Grant administrative privileges');
        $this->line('  2. Remove administrative privileges');
        $this->line('  0. Cancel');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->setAdministrative($user, true),
            '2' => $this->setAdministrative($user, false),
            '0' => $this->info('Operation cancelled.'),
            default => $this->error('Invalid option.'),
        };
    }

    public function manageVesselOwnership(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Vessel Ownership');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Set user as vessel owner');
        $this->line('  2. Remove vessel owner');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->setVesselOwner(),
            '2' => $this->removeVesselOwner(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    public function viewUserInformation(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $this->displayUserInfo($user);
    }

    public function listUsers(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           List Users');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. List all paid users');
        $this->line('  2. List all vessel owners');
        $this->line('  3. List all users');
        $this->line('  4. Search users');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->listPaidUsers(),
            '2' => $this->listOwners(),
            '3' => $this->listAllUsers(),
            '4' => $this->searchUsers(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    public function checkLimits(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Check Limits & Usage');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Check specific user limits');
        $this->line('  2. Check all paid users limits');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->checkUserLimits(),
            '2' => $this->checkAllLimits(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    public function deleteUsers(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Delete Users');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Delete a specific user');
        $this->line('  2. Delete multiple users by email (comma-separated)');
        $this->line('  3. Delete users with no vessels and no transactions');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');
        if ($choice === '0') {
            return;
        }

        match ($choice) {
            '1' => $this->deleteSingleUser(),
            '2' => $this->deleteMultipleUsers(),
            '3' => $this->deleteOrphanedUsers(),
            default => $this->error('Invalid option.'),
        };
    }

    public function manageInvitationLimits(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Invitation Limits');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Reset invitation count for a user (allows 3 more resends)');
        $this->line('  2. Reset invitation count for a user on specific vessel');
        $this->line('  3. View invitation count for a user');
        $this->line('  4. Delete all invitation emails for a user');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');
        if ($choice === '0') {
            return;
        }

        match ($choice) {
            '1' => $this->resetInvitationCount(),
            '2' => $this->resetInvitationCountForVessel(),
            '3' => $this->viewInvitationCount(),
            '4' => $this->deleteInvitationEmails(),
            default => $this->error('Invalid option.'),
        };
    }

    // Helper methods
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

    private function setUserPaid(User $user): void
    {
        if ($user->user_type === 'paid_system') {
            $this->warn("User {$user->email} is already paid_system.");
            return;
        }

        $this->displayUserInfo($user);
        if (!$this->confirm('Set this user as paid_system?', true)) {
            return;
        }

        $user->update(['user_type' => 'paid_system']);
        $this->info("✓ User {$user->email} set as paid_system.");
    }

    private function setUserUnpaid(User $user): void
    {
        if ($user->user_type === 'employee_of_vessel') {
            $this->warn("User {$user->email} is already employee_of_vessel.");
            return;
        }

        $this->displayUserInfo($user);
        $ownedVessels = $user->ownedVessels()->count();
        if ($ownedVessels > 0) {
            $this->warn("⚠ Warning: User owns {$ownedVessels} vessel(s).");
        }

        if (!$this->confirm('Set this user as unpaid (employee_of_vessel)?', true)) {
            return;
        }

        $user->update(['user_type' => 'employee_of_vessel']);
        $this->info("✓ User {$user->email} set as unpaid (employee_of_vessel).");
    }

    private function enableUserLogin(User $user): void
    {
        if ($user->login_permitted) {
            $this->warn("User {$user->email} already has login access enabled.");
            return;
        }

        $user->enableSystemAccess();
        $this->info("✓ Login access enabled for {$user->email}.");
    }

    private function disableUserLogin(User $user): void
    {
        if (!$user->login_permitted) {
            $this->warn("User {$user->email} already has login access disabled.");
            return;
        }

        $user->disableSystemAccess();
        $this->info("✓ Login access disabled for {$user->email}.");
        $this->line("  Temporary password: {$user->temporary_password}");
    }

    private function handleBulkLoginAccess(string $choice): void
    {
        $count = $choice === '3'
            ? User::where('login_permitted', false)->count()
            : User::where('login_permitted', true)->count();

        if ($count === 0) {
            $this->info('No users to update.');
            return;
        }

        $action = $choice === '3' ? 'enable' : 'disable';
        if (!$this->confirm("{$action} login for {$count} user(s)?", false)) {
            return;
        }

        if ($choice === '3') {
            User::where('login_permitted', false)->update([
                'login_permitted' => true,
                'temporary_password' => null,
            ]);
            $this->info("✓ Login enabled for {$count} user(s).");
        } else {
            $users = User::where('login_permitted', true)->get();
            foreach ($users as $user) {
                $user->disableSystemAccess();
            }
            $this->info("✓ Login disabled for {$count} user(s).");
        }
    }

    private function setUserStatus(User $user, string $status): void
    {
        if ($user->status === $status) {
            $this->warn("User {$user->email} already has status: {$status}");
            return;
        }

        $user->update(['status' => $status]);
        $this->info("✓ User {$user->email} status changed to: {$status}");
    }

    private function setAdministrative(User $user, bool $value): void
    {
        if ($user->administrative === $value) {
            $status = $value ? 'has' : 'does not have';
            $this->warn("User {$user->email} already {$status} administrative privileges.");
            return;
        }

        $user->update(['administrative' => $value]);
        $action = $value ? 'granted to' : 'removed from';
        $this->info("✓ Administrative privileges {$action} {$user->email}.");
    }

    private function setVesselOwner(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        if ($user->user_type !== 'paid_system') {
            $this->error("User must be paid_system to own vessels.");
            return;
        }

        $vesselIdentifier = $this->ask('Enter vessel ID or registration number');
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return;
        }

        $this->line("Vessel: {$vessel->name} ({$vessel->registration_number})");
        $this->line("Current owner: " . ($vessel->owner ? $vessel->owner->email : 'None'));

        if (!$this->confirm("Set {$user->email} as owner?", true)) {
            return;
        }

        $vessel->update(['owner_id' => $user->id]);
        $this->info("✓ User {$user->email} is now owner of {$vessel->name}.");
    }

    private function removeVesselOwner(): void
    {
        $vesselIdentifier = $this->ask('Enter vessel ID or registration number');
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return;
        }

        if (!$vessel->owner_id) {
            $this->warn("Vessel {$vessel->name} has no owner.");
            return;
        }

        $this->line("Vessel: {$vessel->name} ({$vessel->registration_number})");
        $this->line("Current owner: {$vessel->owner->email}");

        if (!$this->confirm('Remove owner?', false)) {
            return;
        }

        $vessel->update(['owner_id' => null]);
        $this->info("✓ Owner removed from {$vessel->name}.");
    }

    private function displayUserInfo(User $user): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("User Information: {$user->email}");
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Type: {$user->user_type}");
        $this->line("Status: {$user->status}");
        $this->line("Login Permitted: " . ($user->login_permitted ? 'Yes' : 'No'));
        $this->line("Administrative: " . ($user->administrative ? 'Yes' : 'No'));
        $this->line("Owned Vessels: " . $user->ownedVessels()->count());
        $this->line("Created: {$user->created_at->format('Y-m-d H:i:s')}");
        $this->info('═══════════════════════════════════════════════════════════');
    }

    private function listPaidUsers(): void
    {
        $this->listUsersWithPagination(
            User::where('user_type', 'paid_system')
                ->withCount('ownedVessels')
                ->orderBy('created_at', 'desc'),
            'paid users'
        );
    }

    private function listOwners(): void
    {
        $this->listUsersWithPagination(
            User::whereHas('ownedVessels')
                ->withCount('ownedVessels')
                ->orderBy('owned_vessels_count', 'desc'),
            'vessel owners'
        );
    }

    private function listAllUsers(): void
    {
        $this->listUsersWithPagination(
            User::withCount('ownedVessels')
                ->orderBy('created_at', 'desc'),
            'users'
        );
    }

    private function searchUsers(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Search Users');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $searchTerm = $this->ask('Enter search term (name or email)', '');
        if (empty($searchTerm)) {
            $this->error('Search term is required.');
            return;
        }

        $query = User::withCount('ownedVessels')
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            })
            ->orderBy('created_at', 'desc');

        $this->listUsersWithPagination($query, "users matching '{$searchTerm}'");
    }

    private function listUsersWithPagination($query, string $label): void
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
            $users = (clone $query)
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $headers = ['ID', 'Name', 'Email', 'Type', 'Status', 'Login', 'Vessels', 'Created', 'Updated'];
            $rows = $users->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->user_type,
                    $user->status,
                    $user->login_permitted ? 'Yes' : 'No',
                    $user->owned_vessels_count,
                    $user->created_at->format('Y-m-d H:i'),
                    $user->updated_at->format('Y-m-d H:i'),
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

    private function checkUserLimits(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $vesselCount = $user->ownedVessels()->count();

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("User Limits: {$user->email}");
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("User Type: {$user->user_type}");
        $this->line("Status: {$user->status}");
        $this->line("Current Vessels: {$vesselCount}");
        $this->line("Vessel Limit: Unlimited (not yet implemented)");

        if ($vesselCount > 0) {
            $this->line("\nOwned Vessels:");
            foreach ($user->ownedVessels as $vessel) {
                $this->line("  - {$vessel->name} ({$vessel->registration_number}) - {$vessel->status}");
            }
        }

        $this->info('═══════════════════════════════════════════════════════════');
    }

    private function checkAllLimits(): void
    {
        $users = User::where('user_type', 'paid_system')
            ->withCount('ownedVessels')
            ->orderBy('owned_vessels_count', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No paid users found.');
            return;
        }

        $headers = ['ID', 'Email', 'Type', 'Status', 'Vessels', 'Limit', 'Status'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->email,
                $user->user_type,
                $user->status,
                $user->owned_vessels_count,
                'Unlimited',
                'OK',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$users->count()} user(s)");
    }

    private function deleteSingleUser(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $this->displayUserInfo($user);
        $this->newLine();

        $ownedVessels = $user->ownedVessels()->count();
        $vesselRoles = VesselUserRole::where('user_id', $user->id)->count();
        $vesselUsers = VesselUser::where('user_id', $user->id)->count();
        $transactions = $user->transactions()->count();
        $crewMember = $user->vessel_id ? 'Yes' : 'No';

        $this->warn('⚠ Relationship Summary:');
        $this->line("  - Owned Vessels: {$ownedVessels}");
        $this->line("  - Vessel Roles: {$vesselRoles}");
        $this->line("  - Vessel Users: {$vesselUsers}");
        $this->line("  - Transactions: {$transactions}");
        $this->line("  - Crew Member: {$crewMember}");
        $this->newLine();

        if ($ownedVessels > 0 || $transactions > 0) {
            $this->error('⚠ WARNING: This user has vessels or transactions. Deletion may cause data loss!');
            $this->newLine();
        }

        if (!$this->confirm('Are you sure you want to DELETE this user? This action cannot be undone!', false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        try {
            DB::beginTransaction();

            if ($ownedVessels > 0) {
                Vessel::where('owner_id', $user->id)->update(['owner_id' => null]);
                $this->line("✓ Removed ownership from {$ownedVessels} vessel(s)");
            }

            if ($vesselRoles > 0) {
                VesselUserRole::where('user_id', $user->id)->delete();
                $this->line("✓ Removed {$vesselRoles} vessel role(s)");
            }

            if ($vesselUsers > 0) {
                VesselUser::where('user_id', $user->id)->delete();
                $this->line("✓ Removed {$vesselUsers} vessel user relationship(s)");
            }

            InvitationEmail::where('user_id', $user->id)->delete();

            $userEmail = $user->email;
            $user->delete();

            DB::commit();
            $this->info("✓ User {$userEmail} has been deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Error deleting user: {$e->getMessage()}");
        }
    }

    private function deleteMultipleUsers(): void
    {
        $emailsInput = $this->ask('Enter user emails (comma-separated)');
        if (empty($emailsInput)) {
            $this->error('No emails provided.');
            return;
        }

        $emails = array_map('trim', explode(',', $emailsInput));
        $users = User::whereIn('email', $emails)->get();

        if ($users->isEmpty()) {
            $this->error('No users found with the provided emails.');
            return;
        }

        $this->newLine();
        $this->info('Found ' . $users->count() . ' user(s):');
        foreach ($users as $user) {
            $this->line("  - {$user->email} (ID: {$user->id})");
        }
        $this->newLine();

        if (!$this->confirm('Are you sure you want to DELETE these users? This action cannot be undone!', false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        $deleted = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                DB::beginTransaction();

                Vessel::where('owner_id', $user->id)->update(['owner_id' => null]);
                VesselUserRole::where('user_id', $user->id)->delete();
                VesselUser::where('user_id', $user->id)->delete();
                InvitationEmail::where('user_id', $user->id)->delete();

                $user->delete();
                DB::commit();
                $deleted++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Error deleting {$user->email}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("✓ Deleted {$deleted} user(s) successfully.");
        if ($errors > 0) {
            $this->warn("⚠ {$errors} user(s) could not be deleted.");
        }
    }

    private function deleteOrphanedUsers(): void
    {
        $users = User::whereDoesntHave('ownedVessels')
            ->whereDoesntHave('transactions')
            ->where('user_type', 'employee_of_vessel')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No orphaned users found.');
            return;
        }

        $this->info("Found {$users->count()} orphaned user(s):");
        foreach ($users->take(10) as $user) {
            $this->line("  - {$user->email} (ID: {$user->id})");
        }
        if ($users->count() > 10) {
            $this->line("  ... and " . ($users->count() - 10) . " more");
        }
        $this->newLine();

        if (!$this->confirm("Delete all {$users->count()} orphaned user(s)?", false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        $deleted = 0;
        foreach ($users as $user) {
            try {
                DB::beginTransaction();
                VesselUserRole::where('user_id', $user->id)->delete();
                VesselUser::where('user_id', $user->id)->delete();
                InvitationEmail::where('user_id', $user->id)->delete();
                $user->delete();
                DB::commit();
                $deleted++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Error deleting {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("✓ Deleted {$deleted} orphaned user(s) successfully.");
    }

    private function resetInvitationCount(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $count = InvitationEmail::where('user_id', $user->id)
            ->where('email_type', 'invitation')
            ->count();

        $this->line("Current invitation email count: {$count}");
        $this->newLine();

        if ($count === 0) {
            $this->info('No invitation emails found for this user.');
            return;
        }

        if (!$this->confirm("Delete all {$count} invitation email(s) for {$user->email}? This will allow 3 more resends.", false)) {
            return;
        }

        $deleted = InvitationEmail::where('user_id', $user->id)
            ->where('email_type', 'invitation')
            ->delete();

        $this->info("✓ Deleted {$deleted} invitation email(s). User can now resend invitations (up to 3 times).");
    }

    private function resetInvitationCountForVessel(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $vesselIdentifier = $this->ask('Enter vessel ID or registration number');
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return;
        }

        $count = InvitationEmail::where('user_id', $user->id)
            ->where('vessel_id', $vessel->id)
            ->where('email_type', 'invitation')
            ->count();

        $this->line("Current invitation email count for {$vessel->name}: {$count}");
        $this->newLine();

        if ($count === 0) {
            $this->info("No invitation emails found for {$user->email} on {$vessel->name}.");
            return;
        }

        if (!$this->confirm("Delete all {$count} invitation email(s) for {$user->email} on {$vessel->name}? This will allow 3 more resends.", false)) {
            return;
        }

        $deleted = InvitationEmail::where('user_id', $user->id)
            ->where('vessel_id', $vessel->id)
            ->where('email_type', 'invitation')
            ->delete();

        $this->info("✓ Deleted {$deleted} invitation email(s). User can now resend invitations for this vessel (up to 3 times).");
    }

    private function viewInvitationCount(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info("Invitation Counts: {$user->email}");
        $this->info('═══════════════════════════════════════════════════════════');

        $invitations = InvitationEmail::where('user_id', $user->id)
            ->where('email_type', 'invitation')
            ->with('vessel')
            ->get()
            ->groupBy('vessel_id');

        $totalCount = InvitationEmail::where('user_id', $user->id)
            ->where('email_type', 'invitation')
            ->count();

        $this->line("Total invitation emails sent: {$totalCount}");
        $this->newLine();

        if ($invitations->isEmpty()) {
            $this->info('No invitation emails found.');
            return;
        }

        $this->line('By Vessel:');
        foreach ($invitations as $vesselId => $emails) {
            $vessel = $emails->first()->vessel;
            $vesselName = $vessel ? $vessel->name : "Vessel ID: {$vesselId}";
            $count = $emails->count();
            $canResend = $count < 3 ? 'Yes (can resend ' . (3 - $count) . ' more)' : 'No (limit reached)';
            $this->line("  - {$vesselName}: {$count}/3 - {$canResend}");
        }

        $this->info('═══════════════════════════════════════════════════════════');
    }

    private function deleteInvitationEmails(): void
    {
        $user = $this->selectUser();
        if (!$user) {
            return;
        }

        $count = InvitationEmail::where('user_id', $user->id)->count();

        $this->line("Total invitation emails (all types): {$count}");
        $this->newLine();

        if ($count === 0) {
            $this->info('No invitation emails found for this user.');
            return;
        }

        if (!$this->confirm("Delete ALL {$count} invitation email(s) for {$user->email}? This includes all email types.", false)) {
            return;
        }

        $deleted = InvitationEmail::where('user_id', $user->id)->delete();
        $this->info("✓ Deleted {$deleted} invitation email(s).");
    }
}

