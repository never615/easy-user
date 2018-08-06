<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder\Menu;

use Encore\Admin\Auth\Database\Menu;
use Illuminate\Database\Seeder;

class MenuTablesSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $order = 500;

        $userManagerMenu = $this->updateOrCreate(
            "user_manager", 0, $order++, "会员管理", "fa-user");


        $this->updateOrCreate(
            "users.index", $userManagerMenu->id, $order++, "用户列表", "fa-user");

    }
}
