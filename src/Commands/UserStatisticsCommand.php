<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Commands;

use Illuminate\Console\Command;
use Mallto\User\Domain\Statistics\UserCumulateUsecase;

class UserStatisticsCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:user_statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成用户的统计数据';

    /**
     * @var UserCumulateUsecase
     */
    private $userCumulateUsecase;


    /**
     * UserStatisticsCommand constructor.
     *
     * @param UserCumulateUsecase $userCumulateUsecase
     */
    public function __construct(UserCumulateUsecase $userCumulateUsecase)
    {
        parent::__construct();
        $this->userCumulateUsecase = $userCumulateUsecase;
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->userCumulateUsecase->handle();
    }

}
