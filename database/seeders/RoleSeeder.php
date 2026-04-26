<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Analista']);
        $role3 = Role::create(['name' => 'JCC']);
        $role4 = Role::create(['name' => 'Asesor']);

        Permission::create(['name' => 'admin.index'])->syncRoles([$role1, $role2, $role3, $role4]);

        Permission::create(['name' => 'admin.prestamos.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.prestamos.create'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.prestamos.edit'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.prestamos.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);

        Permission::create(['name' => 'admin.clientes.index'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.clientes.create'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.clientes.edit'])->syncRoles([$role1, $role2, $role3, $role4]);
        Permission::create(['name' => 'admin.clientes.destroy'])->syncRoles([$role1, $role2, $role3, $role4]);

        Permission::create(['name' => 'admin.simulador.index'])->syncRoles([$role1, $role4]);
        Permission::create(['name' => 'admin.simulador.create'])->syncRoles([$role1, $role4]);
        Permission::create(['name' => 'admin.simulador.edit'])->syncRoles([$role1, $role4]);
        Permission::create(['name' => 'admin.simulador.destroy'])->syncRoles([$role1, $role4]);

        Permission::create(['name' => 'admin.sucursales.index'])->syncRoles([$role1, $role2]);
        Permission::create(['name' => 'admin.sucursales.create'])->syncRoles([$role1, $role2]);
        Permission::create(['name' => 'admin.sucursales.edit'])->syncRoles([$role1, $role2]);
        Permission::create(['name' => 'admin.sucursales.destroy'])->syncRoles([$role1, $role2]);

        Permission::create(['name' => 'admin.usuarios.index'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.usuarios.create'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.usuarios.edit'])->syncRoles([$role1]);
        Permission::create(['name' => 'admin.usuarios.destroy'])->syncRoles([$role1]);

    }
}
