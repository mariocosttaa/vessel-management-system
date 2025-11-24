<?php

namespace App\Console\Commands\UserManage\Handlers;

use App\Models\Movimentation;
use App\Models\Supplier;
use App\Models\Marea;
use App\Models\Maintenance;
use App\Models\Vessel;

class FinancialManagementHandler extends BaseHandler
{
    public function listMovimentations(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $limit = (int) $this->ask('Limit (default: 20)', '20');
        $movimentations = Movimentation::where('vessel_id', $vessel->id)
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();

        if ($movimentations->isEmpty()) {
            $this->info("No movimentations found for {$vessel->name}.");
            return;
        }

        $headers = ['ID', 'Number', 'Type', 'Amount', 'Date', 'Status'];
        $rows = $movimentations->map(function ($mov) {
            $date = $mov->transaction_date instanceof \Carbon\Carbon ? $mov->transaction_date->format('Y-m-d') : 'N/A';
            return [
                $mov->id,
                $mov->transaction_number,
                $mov->type,
                number_format($mov->amount / 100, 2) . ' ' . ($mov->currency ?? 'EUR'),
                $date,
                $mov->status,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Showing {$movimentations->count()} movimentation(s)");
    }

    public function listSuppliers(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $suppliers = Supplier::where('vessel_id', $vessel->id)
            ->orderBy('company_name')
            ->get();

        if ($suppliers->isEmpty()) {
            $this->info("No suppliers found for {$vessel->name}.");
            return;
        }

        $headers = ['ID', 'Company', 'Email', 'Phone'];
        $rows = $suppliers->map(function ($supplier) {
            return [
                $supplier->id,
                $supplier->company_name,
                $supplier->email ?? 'N/A',
                $supplier->phone ?? 'N/A',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$suppliers->count()} supplier(s)");
    }

    public function listMareas(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $mareas = Marea::where('vessel_id', $vessel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($mareas->isEmpty()) {
            $this->info("No mareas found for {$vessel->name}.");
            return;
        }

        $headers = ['ID', 'Number', 'Name', 'Status', 'Departure', 'Return'];
        $rows = $mareas->map(function ($marea) {
            $departure = $marea->actual_departure_date
                ? $marea->actual_departure_date->format('Y-m-d')
                : ($marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : 'N/A');
            $return = $marea->actual_return_date
                ? $marea->actual_return_date->format('Y-m-d')
                : ($marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : 'N/A');
            return [
                $marea->id,
                $marea->marea_number,
                $marea->name,
                $marea->status,
                $departure,
                $return,
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$mareas->count()} marea(s)");
    }

    public function listMaintenances(): void
    {
        $vessel = $this->selectVessel();
        if (!$vessel) {
            return;
        }

        $maintenances = Maintenance::where('vessel_id', $vessel->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($maintenances->isEmpty()) {
            $this->info("No maintenances found for {$vessel->name}.");
            return;
        }

        $headers = ['ID', 'Number', 'Name', 'Status', 'Start Date', 'End Date'];
        $rows = $maintenances->map(function ($maintenance) {
            return [
                $maintenance->id,
                $maintenance->maintenance_number,
                $maintenance->name,
                $maintenance->status,
                $maintenance->start_date ? $maintenance->start_date->format('Y-m-d') : 'N/A',
                $maintenance->end_date ? $maintenance->end_date->format('Y-m-d') : 'N/A',
            ];
        })->toArray();

        $this->table($headers, $rows);
        $this->info("Total: {$maintenances->count()} maintenance(s)");
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
}

