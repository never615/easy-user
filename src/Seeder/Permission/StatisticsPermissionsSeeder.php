<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder\Permission;


use Illuminate\Database\Seeder;
use Mallto\Admin\Seeder\SeederMaker;

/**
 * 统计
 * Class StatisticsPermissionsSeeder
 *
 * @package Mallto\Tool\Seeder\Permission
 */
class StatisticsPermissionsSeeder extends Seeder
{

    use SeederMaker;


    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $statisticsManagerPermissionId = $this->createPermissions("统计管理", "statistics_manager", false);
        $this->createPermissions("访问统计", "pv", true, $statisticsManagerPermissionId, true, false, true);
    }
}
