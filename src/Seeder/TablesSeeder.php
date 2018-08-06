<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder;

use Illuminate\Database\Seeder;
use Mallto\User\Seeder\Menu\MenuTablesSeeder;
use Mallto\User\Seeder\Permission\PermissionTablesSeeder;

class TablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MenuTablesSeeder::class);
        $this->call(PermissionTablesSeeder::class);
        //DummySeeder
    }
}
