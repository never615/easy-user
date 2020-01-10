<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 用户授权表添加约束
 * Class CreateUsersTable
 */
class AddUniqueUserAuthsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_auths', function (Blueprint $table) {
            $table->unique([ "subject_id", "identity_type", "identifier" ]);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_auths', function (Blueprint $table) {
            $table->dropUnique([ "subject_id", "identity_type", "identifier" ]);
        });
    }
}
