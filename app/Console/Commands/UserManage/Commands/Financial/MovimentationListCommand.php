<?php

namespace App\Console\Commands\UserManage\Commands\Financial;

use App\Models\Movimentation;
use App\Models\Vessel;
use Illuminate\Console\Command;

class MovimentationListCommand extends Command
{
    protected $signature = 'movimentation:list
                            {vessel : Vessel ID or registration number}
                            {--limit=20 : Number of records to show}
                            {--format=table : Output format (table, json)}';

    protected $description = 'List movimentations for a vessel';

    public function handle(): int
    {
        $identifier = $this->argument('vessel');
        $limit = (int) $this->option('limit');
        $format = $this->option('format');

        $vessel = is_numeric($identifier)
            ? Vessel::find($identifier)
            : Vessel::where('registration_number', $identifier)->first();

        if (!$vessel) {
            $this->error("Vessel not found: {$identifier}");
            return Command::FAILURE;
        }

        $movimentations = Movimentation::where('vessel_id', $vessel->id)
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->get();

        if ($movimentations->isEmpty()) {
            $this->info("No movimentations found for {$vessel->name}.");
            return Command::SUCCESS;
        }

        if ($format === 'json') {
            $data = $movimentations->map(function ($mov) {
                return [
                    'id' => $mov->id,
                    'transaction_number' => $mov->transaction_number,
                    'type' => $mov->type,
                    'amount' => $mov->amount,
                    'currency' => $mov->currency,
                    'transaction_date' => $mov->transaction_date ? $mov->transaction_date->format('Y-m-d') : null,
                    'status' => $mov->status,
                ];
            });

            $this->line($data->toJson(JSON_PRETTY_PRINT));
            return Command::SUCCESS;
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

        return Command::SUCCESS;
    }
}

