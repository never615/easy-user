<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 02/11/2017
 * Time: 2:56 PM
 */

namespace Mallto\User\Domain\Traits;


use Mallto\User\Data\UserAuth;

trait UserAuthTrait
{
    /**
     * passport 授权查找用户的方法,
     * 在Laravel\Passport\Bridge\UserRepository会调用
     *
     * @param $username
     * @return mixed
     */
    public function findForPassport($username)
    {
        \Log::info('findForPassport');
        \Log::info($username);

        $userAuth = UserAuth::where("identity_type", 'mobile')
            ->where("identifier", $username)
            ->first();

        return $userAuth ? $userAuth->user : null;
    }

    /**
     * passport 验证用户的方法
     * 在Laravel\Passport\Bridge\UserRepository会调用
     *
     * @param $password
     */
    public function validateForPassportPasswordGrant($password)
    {
        \Log::info('validateForPassportPasswordGrant');
        \Log::info($password);

        return $this->user->userAuth()
            ->where("credential", $password)
            ->exist();
    }
}