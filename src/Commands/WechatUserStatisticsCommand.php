<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Commands;

use Illuminate\Console\Command;
use Mallto\User\Domain\Statistics\WechatUserCumulateUsecase;

class WechatUserStatisticsCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:wechat_user_statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取微信用户的统计数据';

    /**
     * @var WechatUserCumulateUsecase
     */
    private $wechatUserCumulateUsecase;


    /**
     * WechatUserStatisticsCommand constructor.
     *
     * @param WechatUserCumulateUsecase $wechatUserCumulateUsecase
     */
    public function __construct(WechatUserCumulateUsecase $wechatUserCumulateUsecase)
    {
        parent::__construct();
        $this->wechatUserCumulateUsecase = $wechatUserCumulateUsecase;
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->wechatUserCumulateUsecase->handle();
    }

}
