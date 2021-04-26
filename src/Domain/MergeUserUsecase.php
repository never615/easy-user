<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Domain;

use Illuminate\Support\Facades\DB;

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
        $allTables = $this->getHasKeyTableName('user_id');

        foreach ($allTables as $table) {
            DB::table($table->table_name)
                ->where('user_id', $wechatUser->id)
                ->update([
                    'user_id' => $appUser->id,
                ]);
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


    /**
     * 获取包含key的所有表.
     *
     * @param $key
     *
     * @return array
     */
    protected function getHasKeyTableName($key)
    {
        return DB::select("select b.oid, b.relname as table_name, att.attname, b.relkind,attinhcount, atttypmod
from pg_attribute att, pg_class b
where b.oid = att.attrelid
and att.attname = 'user_id'
and attinhcount in (0)
and b.relkind in ('r')
order by b.relname, atttypmod;");
    }
}
