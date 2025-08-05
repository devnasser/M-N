<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::create(['name' => 'admin']);
        $buyer = Role::create(['name' => 'buyer']);
        $shop = Role::create(['name' => 'shop']);
        $driver = Role::create(['name' => 'driver']);
        $technician = Role::create(['name' => 'technician']);

        // Create permissions
        $permissions = [
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'view_reviews',
            'create_reviews',
            'edit_reviews',
            'delete_reviews',
            'manage_system',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        
        $buyer->givePermissionTo([
            'view_products',
            'create_orders',
            'view_orders',
            'create_reviews',
            'view_reviews',
        ]);

        $shop->givePermissionTo([
            'view_products',
            'create_products',
            'edit_products',
            'view_orders',
            'edit_orders',
            'view_reviews',
        ]);

        $driver->givePermissionTo([
            'view_orders',
            'edit_orders',
            'view_reviews',
        ]);

        $technician->givePermissionTo([
            'view_orders',
            'edit_orders',
            'view_reviews',
        ]);
    }
} 