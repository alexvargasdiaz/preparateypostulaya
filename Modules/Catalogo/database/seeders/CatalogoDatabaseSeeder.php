<?php

declare(strict_types=1);

namespace Modules\Catalogo\Database\Seeders;

use Illuminate\Database\Seeder;

class CatalogoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TipoExamenSeeder::class,
            InstitucionSeeder::class,
            CategoriaSeeder::class,
            ExamenSeeder::class,
        ]);
    }
}
