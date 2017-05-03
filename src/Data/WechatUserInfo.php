<?php

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
