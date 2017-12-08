<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder;

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
        $order = 3000;

        /**
         * --------------------------------  会员管理   -----------------------------------
         */
        $menu = Menu::create([
            'parent_id' => 0,
            'order'     => $order += 1,
            'title'     => '会员管理',
            'icon'      => 'fa-user',
            'uri'       => '',
        ]);

        Menu::insert([
            [
                'parent_id' => $menu->id,
                'order'     => $menu->order += 1,
                'title'     => '用户列表',
                'icon'      => 'fa-user',
                'uri'       => 'users.index',
            ],
        ]);


    }
}
