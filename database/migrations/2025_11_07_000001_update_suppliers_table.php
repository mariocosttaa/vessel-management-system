<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        if (Schema::hasColumn('suppliers', 'name')) {
            DB::table('suppliers')
                ->whereNull('company_name')
                ->update(['company_name' => DB::raw('name')]);
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'tax_number')) {
                try {
                    $table->dropIndex('suppliers_tax_number_index');
                } catch (\Throwable $e) {
                    // Ignore missing index (SQLite does not support conditional drop)
                }
            }

            if (Schema::hasColumn('suppliers', 'status')) {
                try {
                    $table->dropIndex('suppliers_status_index');
                } catch (\Throwable $e) {
                    // Ignore missing index
                }
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('suppliers', 'tax_number')) {
                $table->dropColumn('tax_number');
            }

            if (Schema::hasColumn('suppliers', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'name')) {
                $table->string('name', 255)->nullable();
            }

            if (!Schema::hasColumn('suppliers', 'tax_number')) {
                $table->string('tax_number', 50)->nullable();
            }

            if (!Schema::hasColumn('suppliers', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active');
            }

            if (Schema::hasColumn('suppliers', 'tax_number')) {
                $table->index('tax_number');
            }

            if (Schema::hasColumn('suppliers', 'status')) {
                $table->index('status');
            }
        });
    }
};

