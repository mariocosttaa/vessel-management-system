<?php

namespace App\Console\Commands\UserManage;

use App\Console\Commands\UserManage\Handlers\UserManagementHandler;
use App\Console\Commands\UserManage\Handlers\VesselManagementHandler;
use App\Console\Commands\UserManage\Handlers\CrewManagementHandler;
use App\Console\Commands\UserManage\Handlers\FinancialManagementHandler;
use App\Console\Commands\UserManage\Handlers\SystemManagementHandler;
use Illuminate\Console\Command;

class UserManageCommand extends Command
{
    protected $signature = 'user:manage';

    protected $description = 'Interactive menu to manage users, permissions, vessels, and system';

    private UserManagementHandler $userHandler;
    private VesselManagementHandler $vesselHandler;
    private CrewManagementHandler $crewHandler;
    private FinancialManagementHandler $financialHandler;
    private SystemManagementHandler $systemHandler;

    public function __construct()
    {
        parent::__construct();
        $this->userHandler = new UserManagementHandler($this);
        $this->vesselHandler = new VesselManagementHandler($this);
        $this->crewHandler = new CrewManagementHandler($this);
        $this->financialHandler = new FinancialManagementHandler($this);
        $this->systemHandler = new SystemManagementHandler($this);
    }

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

    private function displayMainMenu(): void
    {
        $this->info('Main Menu:');
        $this->line('═══════════════════════════════════════════════════════════');
        $this->line('  USER MANAGEMENT');
        $this->line('  1. Manage User Type (Paid/Unpaid)');
        $this->line('  2. Manage Login Access');
        $this->line('  3. Manage User Status');
        $this->line('  4. Manage Administrative Privileges');
        $this->line('  5. Manage Vessel Ownership');
        $this->line('  6. View User Information');
        $this->line('  7. List Users');
        $this->line('  8. Check Limits & Usage');
        $this->line('  9. Delete Users');
        $this->line(' 11. Manage Invitation Limits');
        $this->newLine();
        $this->line('  VESSEL MANAGEMENT');
        $this->line(' 20. Create Vessel');
        $this->line(' 21. List Vessels');
        $this->line(' 22. View Vessel Details');
        $this->line(' 23. Update Vessel');
        $this->line(' 24. Delete Vessels');
        $this->newLine();
        $this->line('  CREW MANAGEMENT');
        $this->line(' 30. List Crew Members');
        $this->line(' 31. Assign/Remove Crew Member');
        $this->line(' 32. Manage Crew Positions');
        $this->newLine();
        $this->line('  FINANCIAL MANAGEMENT');
        $this->line(' 40. List Movimentations');
        $this->line(' 41. List Suppliers');
        $this->line(' 42. List Mareas');
        $this->line(' 43. List Maintenances');
        $this->newLine();
        $this->line('  SYSTEM MANAGEMENT');
        $this->line(' 50. View Audit Logs');
        $this->line(' 51. Manage Attachments');
        $this->line(' 52. System Statistics');
        $this->newLine();
        $this->line('  0. Exit');
        $this->newLine();
    }

    private function handleMenuChoice(string $choice): void
    {
        match ($choice) {
            // User Management
            '1' => $this->userHandler->manageUserType(),
            '2' => $this->userHandler->manageLoginAccess(),
            '3' => $this->userHandler->manageUserStatus(),
            '4' => $this->userHandler->manageAdministrative(),
            '5' => $this->userHandler->manageVesselOwnership(),
            '6' => $this->userHandler->viewUserInformation(),
            '7' => $this->userHandler->listUsers(),
            '8' => $this->userHandler->checkLimits(),
            '9' => $this->userHandler->deleteUsers(),
            '11' => $this->userHandler->manageInvitationLimits(),
            // Vessel Management
            '20' => $this->vesselHandler->createVessel(),
            '21' => $this->vesselHandler->listVessels(),
            '22' => $this->vesselHandler->viewVesselDetails(),
            '23' => $this->vesselHandler->updateVessel(),
            '24' => $this->vesselHandler->deleteVessels(),
            // Crew Management
            '30' => $this->crewHandler->listCrewMembers(),
            '31' => $this->crewHandler->assignCrewMember(),
            '32' => $this->crewHandler->manageCrewPositions(),
            // Financial Management
            '40' => $this->financialHandler->listMovimentations(),
            '41' => $this->financialHandler->listSuppliers(),
            '42' => $this->financialHandler->listMareas(),
            '43' => $this->financialHandler->listMaintenances(),
            // System Management
            '50' => $this->systemHandler->viewAuditLogs(),
            '51' => $this->systemHandler->manageAttachments(),
            '52' => $this->systemHandler->systemStatistics(),
            default => $this->error('Invalid option. Please try again.'),
        };
    }
}

