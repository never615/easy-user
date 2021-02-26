<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder\Permission;

use Illuminate\Database\Seeder;
use Mallto\Tool\Seeder\AppSecretSeederMaker;

/**
 * 开放平台接口权限生成
 *
 * Class AppSecretPermissionsSeeder
 *
 * @package Mallto\Tool\Seeder\Permission
 */
class AppSecretPermissionsSeeder extends Seeder
{

    use AppSecretSeederMaker;

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $this->createPermissions('获取短信验证码', 'tp_sms_code.index');
    }
}
