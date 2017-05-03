<?php

namespace Mallto\User\Seeder;

use Illuminate\Database\Seeder;
use Mallto\Mall\Data\AdminUser;
use Mallto\Mall\Data\Subject;

class UserTablesSeeder extends Seeder
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
    }
}
