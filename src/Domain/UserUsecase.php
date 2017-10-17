<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */


namespace Mallto\User\Domain;


use Mallto\User\Data\User;


/**
 * Created by PhpStorm.
 * User: never615 <never615.com>
 * Date: 31/07/2017
 * Time: 5:00 PM
 */
interface UserUsecase
{
    /**
     * 判断用户是否存在
     *
     * @param        $type
     * @param bool   $register  ,注册或者登录模式,
     *                          当type是mobile的情况下:
     *                          注册模式下还需要判断注册的手机号是否已经存在
     * @param string $requestType
     * @return bool|User
     */
    public function existUser($type = null, $register = true, $requestType = "");


    /**
     * 创建用户
     *
     * @param string $type ,mobile or email ..
     * @param        $info
     * @param string $requestType
     * @return
     */
    public function createUser($type = null, $info = null, $requestType = "");

    /**
     * 获取用户信息
     *
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function getUserInfo($userId);

    /**
     * 获取openid
     *
     * @return mixed
     */
    public function getOpenid();

    /**
     * @param $uuid
     * @param $openid
     * @return mixed
     */
    public function getWechatUserInfo($uuid, $openid);


}
