<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateUsersTable
 */
class AddTopSubjectForUser extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger("top_subject_id")->nullable();
        });

        Schema::table('user_auths', function (Blueprint $table) {
            $table->unsignedInteger("top_subject_id")->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("top_subject_id");
        });

        Schema::table('user_auths', function (Blueprint $table) {
            $table->dropColumn("top_subject_id");
        });
    }
}
