<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Commands;

use Illuminate\Console\Command;
use Mallto\User\Data\User;

/**
 * 用户授权添加手机sms的auth方式
 *
 * Class UserAuthAddMobileSmsCommand
 *
 * @package Mallto\Admin\Console
 */
class UserAuthAddSmsCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:user_auth_sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '用户授权添加手机验证码方式';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('task start!');

        $count = User::whereNotNull("mobile")
            ->count();

        $bar = $this->output->createProgressBar($count / 100);

        User::whereNotNull("mobile")
            ->chunk(100, function ($users) use ($bar) {
                foreach ($users as $user) {
                    $user->userAuths()->firstOrCreate([
                        "identity_type" => "sms",
                        "identifier"    => $user->mobile,
                        "subject_id"    => $user->subject_id,
                    ]);
                }
                $bar->advance(1);
            });

        $bar->finish();
        $this->info('task finished!');
    }

}
