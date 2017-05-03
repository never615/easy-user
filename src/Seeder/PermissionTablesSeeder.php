<?php

namespace Mallto\User\Seeder;

use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Seeder\SeederMaker;
use Illuminate\Database\Seeder;

class PermissionTablesSeeder extends Seeder
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

        /**
         * ------------------------  店铺  ---------------------------
         */
        $this->createPermissions("用户", "users");
    }
}
