<?php

$files = [
    'User' => ['UserSetPaid', 'UserSetUnpaid', 'UserSetEmployee', 'UserEnableLogin', 'UserDisableLogin', 'UserSetStatus', 'UserSetAdministrative', 'UserSetOwner', 'UserRemoveOwner', 'UserShowInfo', 'UserListPaid', 'UserListOwners', 'UserCheckLimits'],
    'Vessel' => ['VesselCreate', 'VesselList', 'VesselShow', 'VesselUpdate'],
    'Crew' => ['CrewList', 'CrewAssign', 'AssignRolesToCrewPositions'],
    'Financial' => ['MovimentationList', 'SupplierList', 'MareaList', 'MaintenanceList'],
    'System' => ['SystemStats', 'AuditLogView'],
];

foreach ($files as $category => $commands) {
    foreach ($commands as $cmd) {
        $oldFile = "app/Console/Commands/{$cmd}.php";
        $newFile = "app/Console/Commands/UserManage/Commands/{$category}/{$cmd}Command.php";

        if (!file_exists($oldFile)) {
            echo "⚠ File not found: {$oldFile}\n";
            continue;
        }

        $content = file_get_contents($oldFile);

        // Update namespace
        $content = str_replace(
            'namespace App\Console\Commands;',
            "namespace App\Console\Commands\UserManage\Commands\\{$category};",
            $content
        );

        // Update class name
        $content = preg_replace(
            "/class {$cmd}/",
            "class {$cmd}Command",
            $content
        );

        // Ensure directory exists
        $dir = dirname($newFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Write new file
        file_put_contents($newFile, $content);
        echo "✓ Moved: {$cmd} -> {$cmd}Command\n";
    }
}

echo "\nDone! Files moved. You can now delete the old files.\n";

