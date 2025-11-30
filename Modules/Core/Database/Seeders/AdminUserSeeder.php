<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\User;
use Modules\Customer\Entities\Customer;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // ==================== CREAR PERMISOS ====================
            $permissions = [
                // Usuarios
                'users.view', 'users.create', 'users.edit', 'users.delete',

                // Roles
                'roles.view', 'roles.create', 'roles.edit', 'roles.delete',

                // Clientes
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',

                // Contratos
                'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',

                // Facturación
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
                'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
                'credit_notes.view', 'credit_notes.create', 'credit_notes.edit',

                // Servicios
                'services.view', 'services.create', 'services.edit', 'services.delete',
                'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
                'promotions.view', 'promotions.create', 'promotions.edit', 'promotions.delete',

                // Instalaciones
                'installations.view', 'installations.create', 'installations.edit', 'installations.delete',

                // Rutas
                'routes.view', 'routes.create', 'routes.edit', 'routes.delete',

                // SLAs
                'slas.view', 'slas.create', 'slas.edit', 'slas.delete',

                // Configuración
                'configuration.view', 'configuration.edit',

                // Auditoría
                'audit.view', 'audit.export',

                // Seguridad
                'security.view', 'security.edit',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            $this->command->info('✅ Permisos creados');

            // ==================== CREAR ROLES ====================

            // ROL: Super Admin
            $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
            $superAdminRole->givePermissionTo(Permission::all());

            // ROL: Administrador
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $adminRole->givePermissionTo([
                'users.view', 'users.create', 'users.edit',
                'customers.view', 'customers.create', 'customers.edit',
                'contracts.view', 'contracts.create', 'contracts.edit',
                'invoices.view', 'invoices.create', 'invoices.edit',
                'payments.view', 'payments.create',
                'services.view', 'plans.view', 'promotions.view',
            ]);

            // ROL: Cliente
            $customerRole = Role::firstOrCreate(['name' => 'customer']);
            $customerRole->givePermissionTo([
                // Los clientes solo ven su propia información
            ]);

            // ROL: Técnico
            $techRole = Role::firstOrCreate(['name' => 'technician']);
            $techRole->givePermissionTo([
                'installations.view', 'installations.edit',
                'routes.view',
                'customers.view',
            ]);

            $this->command->info('✅ Roles creados');

            // ==================== CREAR USUARIO SUPER ADMIN ====================

            $superAdmin = User::firstOrCreate(
                ['email' => 'admin@noretel.com'],
                [
                    'username' => 'superadmin',
                    'password' => Hash::make('Admin123!'),
                    'status' => 'active',
                    'requires_2fa' => false,
                    'email_verified_at' => now(),
                ]
            );

            $superAdmin->assignRole($superAdminRole);

            $this->command->info('✅ Super Admin creado');

            // ==================== CREAR USUARIO ADMIN ====================

            $admin = User::firstOrCreate(
                ['email' => 'admin2@noretel.com'],
                [
                    'username' => 'admin',
                    'password' => Hash::make('Admin123!'),
                    'status' => 'active',
                    'requires_2fa' => false,
                    'email_verified_at' => now(),
                ]
            );

            $admin->assignRole($adminRole);

            $this->command->info('✅ Administrador creado');

            // ==================== CREAR USUARIO CLIENTE DE PRUEBA ====================

            $testUser = User::firstOrCreate(
                ['email' => 'cliente@test.com'],
                [
                    'username' => 'cliente.test',
                    'password' => Hash::make('Cliente123!'),
                    'status' => 'active',
                    'requires_2fa' => false,
                    'email_verified_at' => now(),
                ]
            );

            $testUser->assignRole($customerRole);

            // Crear cliente asociado
            Customer::firstOrCreate(
                ['email' => 'cliente@test.com'],
                [
                    'user_id' => $testUser->id,
                    'customer_type' => 'individual',
                    'first_name' => 'Cliente',
                    'last_name' => 'Prueba',
                    'phone' => '987654321',
                    'customer_status' => 'active',
                    'registration_date' => now(),
                    'active' => true,
                ]
            );

            $this->command->info('✅ Cliente de prueba creado');

            DB::commit();

            // ==================== MOSTRAR CREDENCIALES ====================

            $this->command->info('');
            $this->command->info('═══════════════════════════════════════════════');
            $this->command->info('   USUARIOS CREADOS EXITOSAMENTE');
            $this->command->info('═══════════════════════════════════════════════');
            $this->command->info('');
            $this->command->info('🔐 SUPER ADMINISTRADOR:');
            $this->command->info('   Email: admin@noretel.com');
            $this->command->info('   Password: Admin123!');
            $this->command->info('');
            $this->command->info('👤 ADMINISTRADOR:');
            $this->command->info('   Email: admin2@noretel.com');
            $this->command->info('   Password: Admin123!');
            $this->command->info('');
            $this->command->info('👥 CLIENTE DE PRUEBA:');
            $this->command->info('   Email: cliente@test.com');
            $this->command->info('   Password: Cliente123!');
            $this->command->info('');
            $this->command->info('═══════════════════════════════════════════════');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
