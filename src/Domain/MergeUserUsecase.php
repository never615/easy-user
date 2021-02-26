<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeUserUsecase
{

    /**
     * 合并用户数据.
     *
     * @param $appUser
     * @param $wechatUser
     */
    public function mergeUserData($appUser, $wechatUser)
    {
        //获取所有表
        $allTables = $this->getAllTableName();

        //todo 这里如果要合并用户消费数据，会员等级那些则需要计算

        //将微信用户的数据改为手机号用户
        foreach ($allTables as $table) {
            //获取拥有user_id字段的表
            $bool = Schema::hasColumn($table->table_name, 'user_id');

            if ($bool) {
                DB::table($table->table_name)
                    ->where('user_id', $wechatUser->id)
                    ->update([
                        'user_id' => $appUser->id,
                    ]);
            }
        }
    }


    /**
     * 获取当前数据库所有数据表.
     *
     * @return array
     */
    protected function getAllTableName()
    {
        return DB::select("SELECT relname AS table_name FROM pg_class WHERE relkind = 'r' AND relname NOT LIKE'pg_%' AND relname NOT LIKE'sql_%'");
    }
}
