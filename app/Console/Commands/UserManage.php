<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vessel;
use Illuminate\Console\Command;

class UserManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive menu to manage users, permissions, and vessel ownership';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           User Management System');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        while (true) {
            $this->displayMainMenu();
            $choice = $this->ask('Select an option', '0');

            if ($choice === '0') {
                $this->info('Goodbye!');
                break;
            }

            $this->handleMenuChoice($choice);
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    /**
     * Display the main menu.
     */
    private function displayMainMenu(): void
    {
        $this->info('Main Menu:');
        $this->line('  1. Manage User Type (Paid/Unpaid)');
        $this->line('  2. Manage Login Access');
        $this->line('  3. Manage User Status');
        $this->line('  4. Manage Administrative Privileges');
        $this->line('  5. Manage Vessel Ownership');
        $this->line('  6. View User Information');
        $this->line('  7. List Users');
        $this->line('  8. Check Limits & Usage');
        $this->line('  0. Exit');
        $this->newLine();
    }

    /**
     * Handle menu choice.
     */
    private function handleMenuChoice(string $choice): void
    {
        match ($choice) {
            '1' => $this->manageUserType(),
            '2' => $this->manageLoginAccess(),
            '3' => $this->manageUserStatus(),
            '4' => $this->manageAdministrative(),
            '5' => $this->manageVesselOwnership(),
            '6' => $this->viewUserInformation(),
            '7' => $this->listUsers(),
            '8' => $this->checkLimits(),
            default => $this->error('Invalid option. Please try again.'),
        };
    }

    /**
     * Manage user type (paid/unpaid).
     */
    private function manageUserType(): void
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
        if (! $user) {
            return;
        }

        match ($subChoice) {
            '1' => $this->setUserPaid($user),
            '2' => $this->setUserUnpaid($user),
            default => $this->error('Invalid option.'),
        };
    }

    /**
     * Manage login access.
     */
    private function manageLoginAccess(): void
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
        if (! $user) {
            return;
        }

        match ($subChoice) {
            '1' => $this->enableUserLogin($user),
            '2' => $this->disableUserLogin($user),
            default => $this->error('Invalid option.'),
        };
    }

    /**
     * Manage user status.
     */
    private function manageUserStatus(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage User Status');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $user = $this->selectUser();
        if (! $user) {
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

        $statusMap = [
            '1' => 'active',
            '2' => 'inactive',
            '3' => 'on_leave',
        ];

        if ($choice === '0' || ! isset($statusMap[$choice])) {
            $this->info('Operation cancelled.');
            return;
        }

        $this->setUserStatus($user, $statusMap[$choice]);
    }

    /**
     * Manage administrative privileges.
     */
    private function manageAdministrative(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Administrative Privileges');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $user = $this->selectUser();
        if (! $user) {
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

    /**
     * Manage vessel ownership.
     */
    private function manageVesselOwnership(): void
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

    /**
     * View user information.
     */
    private function viewUserInformation(): void
    {
        $user = $this->selectUser();
        if (! $user) {
            return;
        }

        $this->displayUserInfo($user);
    }

    /**
     * List users.
     */
    private function listUsers(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           List Users');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. List all paid users');
        $this->line('  2. List all vessel owners');
        $this->line('  3. List all users');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->listPaidUsers(),
            '2' => $this->listOwners(),
            '3' => $this->listAllUsers(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    /**
     * Check limits.
     */
    private function checkLimits(): void
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

    /**
     * Select a user interactively.
     */
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

        if (! $user) {
            $this->error("User not found: {$identifier}");
            return null;
        }

        return $user;
    }

    /**
     * Set user as paid.
     */
    private function setUserPaid(User $user): void
    {
        if ($user->user_type === 'paid_system') {
            $this->warn("User {$user->email} is already paid_system.");
            return;
        }

        $this->displayUserInfo($user);

        if (! $this->confirm('Set this user as paid_system?', true)) {
            return;
        }

        $user->update(['user_type' => 'paid_system']);
        $this->info("✓ User {$user->email} set as paid_system.");
    }

    /**
     * Set user as unpaid.
     */
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

        if (! $this->confirm('Set this user as unpaid (employee_of_vessel)?', true)) {
            return;
        }

        $user->update(['user_type' => 'employee_of_vessel']);
        $this->info("✓ User {$user->email} set as unpaid (employee_of_vessel).");
    }

    /**
     * Enable user login.
     */
    private function enableUserLogin(User $user): void
    {
        if ($user->login_permitted) {
            $this->warn("User {$user->email} already has login access enabled.");
            return;
        }

        $user->enableSystemAccess();
        $this->info("✓ Login access enabled for {$user->email}.");
    }

    /**
     * Disable user login.
     */
    private function disableUserLogin(User $user): void
    {
        if (! $user->login_permitted) {
            $this->warn("User {$user->email} already has login access disabled.");
            return;
        }

        $user->disableSystemAccess();
        $this->info("✓ Login access disabled for {$user->email}.");
        $this->line("  Temporary password: {$user->temporary_password}");
    }

    /**
     * Handle bulk login access operations.
     */
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
        if (! $this->confirm("{$action} login for {$count} user(s)?", false)) {
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

    /**
     * Set user status.
     */
    private function setUserStatus(User $user, string $status): void
    {
        if ($user->status === $status) {
            $this->warn("User {$user->email} already has status: {$status}");
            return;
        }

        $user->update(['status' => $status]);
        $this->info("✓ User {$user->email} status changed to: {$status}");
    }

    /**
     * Set administrative privileges.
     */
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

    /**
     * Set vessel owner.
     */
    private function setVesselOwner(): void
    {
        $user = $this->selectUser();
        if (! $user) {
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

        if (! $vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return;
        }

        $this->line("Vessel: {$vessel->name} ({$vessel->registration_number})");
        $this->line("Current owner: " . ($vessel->owner ? $vessel->owner->email : 'None'));

        if (! $this->confirm("Set {$user->email} as owner?", true)) {
            return;
        }

        $vessel->update(['owner_id' => $user->id]);
        $this->info("✓ User {$user->email} is now owner of {$vessel->name}.");
    }

    /**
     * Remove vessel owner.
     */
    private function removeVesselOwner(): void
    {
        $vesselIdentifier = $this->ask('Enter vessel ID or registration number');
        $vessel = is_numeric($vesselIdentifier)
            ? Vessel::find($vesselIdentifier)
            : Vessel::where('registration_number', $vesselIdentifier)->first();

        if (! $vessel) {
            $this->error("Vessel not found: {$vesselIdentifier}");
            return;
        }

        if (! $vessel->owner_id) {
            $this->warn("Vessel {$vessel->name} has no owner.");
            return;
        }

        $this->line("Vessel: {$vessel->name} ({$vessel->registration_number})");
        $this->line("Current owner: {$vessel->owner->email}");

        if (! $this->confirm('Remove owner?', false)) {
            return;
        }

        $vessel->update(['owner_id' => null]);
        $this->info("✓ Owner removed from {$vessel->name}.");
    }

    /**
     * List paid users.
     */
    private function listPaidUsers(): void
    {
        $users = User::where('user_type', 'paid_system')
            ->withCount('ownedVessels')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No paid users found.');
            return;
        }

        $headers = ['ID', 'Name', 'Email', 'Status', 'Login', 'Vessels'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->status,
                $user->login_permitted ? 'Yes' : 'No',
                $user->owned_vessels_count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$users->count()} paid user(s)");
    }

    /**
     * List owners.
     */
    private function listOwners(): void
    {
        $users = User::whereHas('ownedVessels')
            ->withCount('ownedVessels')
            ->orderBy('owned_vessels_count', 'desc')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No vessel owners found.');
            return;
        }

        $headers = ['ID', 'Name', 'Email', 'Type', 'Status', 'Vessels'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->user_type,
                $user->status,
                $user->owned_vessels_count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $totalVessels = $users->sum('owned_vessels_count');
        $this->info("Total: {$users->count()} owner(s) with {$totalVessels} vessel(s)");
    }

    /**
     * List all users.
     */
    private function listAllUsers(): void
    {
        $users = User::withCount('ownedVessels')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');
            return;
        }

        $headers = ['ID', 'Name', 'Email', 'Type', 'Status', 'Login', 'Vessels'];
        $rows = $users->map(function ($user) {
            return [
                $user->id,
                $user->name,
                $user->email,
                $user->user_type,
                $user->status,
                $user->login_permitted ? 'Yes' : 'No',
                $user->owned_vessels_count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Showing {$users->count()} user(s)");
    }

    /**
     * Check user limits.
     */
    private function checkUserLimits(): void
    {
        $user = $this->selectUser();
        if (! $user) {
            return;
        }

        $this->displayUserLimits($user);
    }

    /**
     * Check all limits.
     */
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

    /**
     * Display user information.
     */
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

    /**
     * Display user limits.
     */
    private function displayUserLimits(User $user): void
    {
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
}

