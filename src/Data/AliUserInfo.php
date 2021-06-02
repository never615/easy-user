<?php
/**
 * Copyright (c) 2018. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;

use Mallto\Admin\Data\Traits\BaseModel;

class AliUserInfo extends BaseModel
{

    protected $guarded = [];

    const SEX = [
        'F' => '女性',
        'M' => '男性',
    ];

}
