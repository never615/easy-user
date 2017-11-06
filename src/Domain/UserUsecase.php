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
     * 从请求中提取用户凭证
     *
     * @param $request
     * @return array
     */
    public function transformCredentials($request);

    /**
     * 给user对象添加token
     * token分不同的类型,即不同的作用域,比如有普通的微信令牌和绑定手机的微信用户的令牌
     *
     * @param $user
     */
    public function addToken($user);

    /**
     * 根据标识符检查用户是否通过验证
     *
     * 1. 登录的时候需要用
     * 2. 注册的时候也可以使用来判断用户是否存在
     *
     * @param $credentials
     * @param $subject
     * @return User|null
     */
    public function retrieveByCredentials($credentials, $subject);

    /**
     * 绑定数据(如:手机,邮箱)
     *
     * @param $user
     * @param $bindType
     * @param $bindData
     * @return mixed
     */
    public function bind($user, $bindType, $bindData);

    /**
     * 检查用户的绑定状态
     * 存在对应绑定项目,返回true;否则返回false
     *
     * @param $user
     * @param $bindType
     * @return bool
     */
    public function checkUserBindStatus($user, $bindType);

    /**
     * 检查对应项是否已经被绑定,注册可用
     * 如:检查手机号是否被绑定
     *
     * @param $bindDate
     * @param $bindType
     * @param $subjectId
     */
    public function isBinded($bindDate, $bindType, $subjectId);

    /**
     * 解密openid
     *
     * @param $openid
     * @return string
     */
    public function decryptOpenid($openid);


    /**
     * 微信:创建用户
     *
     * @param      $credentials
     * @param      $subject
     * @param null $info
     * @return User
     */
    public function createUserByWechat($credentials, $subject, $info = null);


    /**
     * 更新用户的微信信息
     *
     * @param $user
     * @param $credentials
     * @param $subject
     */
    public function updateUserWechatInfo($user, $credentials, $subject);


    /**
     * 获取用户信息
     * 默认实现即返回用户对象,不同的项目可以需要返回的不一样.
     * 比如:mall项目需要返回member信息等,不同项目可以有自己不同的实现
     *
     * @param $user
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function getReturenUserInfo($user);


}
