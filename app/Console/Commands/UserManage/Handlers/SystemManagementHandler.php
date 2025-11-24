<?php

namespace App\Console\Commands\UserManage\Handlers;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Movimentation;
use App\Models\Marea;
use App\Models\Maintenance;
use App\Models\Supplier;
use App\Models\Attachment;

class SystemManagementHandler extends BaseHandler
{
    public function viewAuditLogs(): void
    {
        $limit = (int) $this->ask('Number of logs to show (default: 20)', '20');
        $logs = AuditLog::orderBy('created_at', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No audit logs found.');
            return;
        }

        $headers = ['ID', 'User', 'Action', 'Model', 'Created'];
        $rows = $logs->map(function ($log) {
            return [
                $log->id,
                $log->user ? $log->user->email : 'System',
                $log->action,
                $log->model_type ?? 'N/A',
                $log->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Showing {$logs->count()} log(s)");
    }

    public function manageAttachments(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           Manage Attachments');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('Options:');
        $this->line('  1. Delete attachment');
        $this->line('  0. Back to main menu');
        $this->newLine();

        $choice = $this->ask('Select an option', '0');

        match ($choice) {
            '1' => $this->deleteAttachment(),
            '0' => null,
            default => $this->error('Invalid option.'),
        };
    }

    public function systemStatistics(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('           System Statistics');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $totalUsers = User::count();
        $paidUsers = User::where('user_type', 'paid_system')->count();
        $unpaidUsers = User::where('user_type', 'employee_of_vessel')->count();
        $totalVessels = Vessel::count();
        $activeVessels = Vessel::where('status', 'active')->count();
        $totalCrew = User::whereNotNull('vessel_id')->count();
        $totalMovimentations = Movimentation::count();
        $totalMareas = Marea::count();
        $totalMaintenances = Maintenance::count();
        $totalSuppliers = Supplier::count();

        $this->line("Total Users: {$totalUsers}");
        $this->line("  - Paid Users: {$paidUsers}");
        $this->line("  - Unpaid Users: {$unpaidUsers}");
        $this->line("Total Vessels: {$totalVessels}");
        $this->line("  - Active Vessels: {$activeVessels}");
        $this->line("Total Crew Members: {$totalCrew}");
        $this->line("Total Movimentations: {$totalMovimentations}");
        $this->line("Total Suppliers: {$totalSuppliers}");
        $this->line("Total Mareas: {$totalMareas}");
        $this->line("Total Maintenances: {$totalMaintenances}");
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
    }

    private function deleteAttachment(): void
    {
        $attachmentId = $this->ask('Enter attachment ID');
        if (empty($attachmentId) || !is_numeric($attachmentId)) {
            $this->error('Valid attachment ID is required.');
            return;
        }

        $attachment = Attachment::find($attachmentId);
        if (!$attachment) {
            $this->error('Attachment not found.');
            return;
        }

        if ($this->confirm("Delete attachment '{$attachment->name}'?", false)) {
            $attachment->delete();
            $this->info("✓ Attachment deleted successfully.");
        }
    }
}

