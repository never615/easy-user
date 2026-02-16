<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Seeder\Permission\Tp;

use Mallto\Tool\Seeder\Permission\Tp\TpApiPermissionBaseSeeder;

/**
 * 开放平台接口权限生成
 *
 * Class AppSecretPermissionsSeeder
 *
 * @package Mallto\Tool\Seeder\Permission
 */
class TpApiPermissionsSeeder extends TpApiPermissionBaseSeeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $this->createPermissions('获取短信验证码', 'sms_code.index', false);
    }
}
