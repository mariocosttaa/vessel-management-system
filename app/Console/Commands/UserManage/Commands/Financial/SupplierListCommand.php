<?php

namespace App\Console\Commands\UserManage\Commands\Financial;

use App\Models\Supplier;
use App\Models\Vessel;
use Illuminate\Console\Command;

class SupplierListCommand extends Command
{
    protected $signature = 'supplier:list
                            {vessel : Vessel ID or registration number}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List suppliers for a vessel';

    public function handle(): int
    {
        $identifier = $this->argument('vessel');
        $format = $this->option('format');

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return Command::FAILURE;
        }

        $suppliers = Supplier::where('vessel_id', $vessel->id)
            ->orderBy('company_name')
            ->get();

        if ($suppliers->isEmpty()) {
            $this->info("No suppliers found for {$vessel->name}.");
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $suppliers->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'company_name' => $supplier->company_name,
                    'email' => $supplier->email,
                    'phone' => $supplier->phone,
                    'address' => $supplier->address,
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
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

        return Command::SUCCESS;
    }
}

