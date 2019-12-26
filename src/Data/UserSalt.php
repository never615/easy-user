<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Mallto\User\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * 验证用户
 * Class UserAuth
 *
 * @package App\Module\User
 */
class UserSalt extends Model
{

    protected $guarded = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
