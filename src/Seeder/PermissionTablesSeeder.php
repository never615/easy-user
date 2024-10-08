<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder;

use Illuminate\Database\Seeder;
use Mallto\User\Seeder\Permission\AppSecretPermissionsSeeder;
use Mallto\User\Seeder\Permission\StatisticsPermissionsSeeder;
use Mallto\User\Seeder\Permission\UserPermissionTablesSeeder;

class PermissionTablesSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserPermissionTablesSeeder::class);
        $this->call(AppSecretPermissionsSeeder::class);
        $this->call(StatisticsPermissionsSeeder::class);
//DummySeeder
    }
}
