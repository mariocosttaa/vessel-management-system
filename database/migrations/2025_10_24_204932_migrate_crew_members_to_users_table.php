<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing crew members to users table
        $crewMembers = DB::table('crew_members')
            ->whereNull('deleted_at')
            ->get();

        foreach ($crewMembers as $crewMember) {
            DB::table('users')->insert([
                'name' => $crewMember->name,
                'email' => $crewMember->email ?: strtolower(str_replace(' ', '.', $crewMember->name)) . '@crew.vessel',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // default password
                'user_type' => 'employee_of_vessel',
                'vessel_id' => $crewMember->vessel_id,
                'position_id' => $crewMember->position_id,
                'phone' => $crewMember->phone,
                'date_of_birth' => $crewMember->date_of_birth,
                'hire_date' => $crewMember->hire_date,
                'salary_amount' => $crewMember->salary_amount,
                'salary_currency' => $crewMember->salary_currency,
                'house_of_zeros' => $crewMember->house_of_zeros,
                'payment_frequency' => $crewMember->payment_frequency,
                'status' => $crewMember->status,
                'notes' => $crewMember->notes,
                'login_permitted' => false, // Crew members don't have system access by default
                'temporary_password' => 'temp_' . time() . '_' . $crewMember->id,
                'created_at' => $crewMember->created_at,
                'updated_at' => $crewMember->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated crew members from users table
        DB::statement("
            DELETE FROM users
            WHERE user_type = 'employee_of_vessel'
            AND login_permitted = false
            AND temporary_password IS NOT NULL
        ");
    }
};
