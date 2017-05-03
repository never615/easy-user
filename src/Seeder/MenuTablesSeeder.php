<?php

namespace Mallto\User\Seeder;

use Encore\Admin\Auth\Database\Menu;
use Illuminate\Database\Seeder;
use Mallto\Mall\Data\AdminUser;
use Mallto\Mall\Data\Subject;

class MenuTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu=Menu::where("title","会员管理")->first();

        Menu::insert([
            [
                'parent_id' => $menu->id,
                'order'     => $menu->order += 1,
                'title'     => '用户列表',
                'icon'      => 'fa-user',
                'uri'       => 'users.index',
            ]
        ]);

      

    }
}
