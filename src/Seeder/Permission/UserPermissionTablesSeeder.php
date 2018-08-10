<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder\Permission;

use Illuminate\Database\Seeder;
use Mallto\Admin\Seeder\SeederMaker;

class UserPermissionTablesSeeder extends Seeder
{

    use SeederMaker;

    protected $order = 1000;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createPermissions("用户", "users");
    }
}
