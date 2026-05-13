<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Prompts\Output\ConsoleOutput;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Helper\ProgressBar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = now();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $seedPermissions = [
            "read-users",

            "create-categories", "read-categories", "update-categories", "delete-categories",

            "create-own-categories", "read-own-categories", "update-own-categories", "delete-own-categories",

            'user-register'
        ];

        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, count($seedPermissions));
        $output->writeln("");
        $output->writeln('Seed Permissions');
        $progress->start();

        foreach ($seedPermissions as $newPermission) {
            $newPermission = Str::of($newPermission)->kebab();

            $permission = Permission::firstOrCreate(['name' => $newPermission]);
            $progress->advance();
        }

        $progress->finish();
        $output->writeln('');

        $output->writeln('Create Roles with Permissions');
        $output->writeln('');

        $progress = new ProgressBar($output, 4);
        $output->writeln("");
        $output->writeln('Grant Permissions to Roles');
        $progress->start();

        /* Create Admin Role and Sync Permissions */

        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);

        $adminPermissions = [
            "read-users",

            "create-categories", "read-categories", "update-categories", "delete-categories",
        ];

        foreach ($adminPermissions as $key => $permission) {
            $adminPermissions[$key] = Str::of($permission)->kebab()->toString();
        }

        $roleAdmin->syncPermissions($adminPermissions);
        $progress->advance();

        $roleClient = Role::firstOrCreate(['name' => 'client']);

        $clientPermissions = [
            "create-own-categories", "read-own-categories", "update-own-categories", "delete-own-categories",
        ];

        foreach ($clientPermissions as $key => $permission) {
            $clientPermissions[$key] = Str::of($permission)->kebab()->toString();
        }

        $roleClient->syncPermissions($clientPermissions);
        $progress->advance();

        /* Permission-less Roles */

        $output->writeln("Adding roles, without permissions");

        $guestClient = Role::firstOrCreate(['name' => 'guest']);

        $guestPermissions = [
            'user-register'
        ];

        foreach ($guestPermissions as $key => $permission) {
            $guestPermissions[$key] = Str::of($permission)->kebab()->toString();
        }

        $guestClient->syncPermissions($guestPermissions);
        $progress->advance();

        $progress->finish();
        $output->writeln(" ");

        $time = $start->diffInSeconds(now());
        $output->writeln("Roles & Permissions completed: $time seconds");
        $output->writeln(" ");
    }
}
