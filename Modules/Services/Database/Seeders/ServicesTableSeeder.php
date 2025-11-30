<?php

namespace Modules\Services\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Services\Entities\Service;

class ServicesTableSeeder extends Seeder
{
    public function run()
    {
        Service::insert([
            [
                'name' => 'Internet Fibra Óptica',
                'description' => 'Servicio de internet de alta velocidad vía fibra óptica',
                'service_type' => 'internet',
                'technology' => 'ftth',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Internet Cable',
                'description' => 'Servicio de internet por cable coaxial',
                'service_type' => 'internet',
                'technology' => 'cable',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Internet Inalámbrico',
                'description' => 'Servicio de internet por tecnología inalámbrica',
                'service_type' => 'internet',
                'technology' => 'wireless',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
