<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder;

use Illuminate\Database\Seeder;
use Mallto\User\Seeder\Menu\UserMenuTablesSeeder;
use Mallto\User\Seeder\Permission\UserPermissionTablesSeeder;

class MenuTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserMenuTablesSeeder::class);
//DummySeeder
    }
}
