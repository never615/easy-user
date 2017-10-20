<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;

use Illuminate\Database\Eloquent\Model;


class WechatUserInfo extends Model
{

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'wechat_public';

    protected $guarded = [

    ];
}
