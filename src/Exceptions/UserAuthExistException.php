<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

/**
 * Created by PhpStorm.
 * User: never615
 * Date: 12/07/2017
 * Time: 3:03 PM
 */

namespace Mallto\User\Exceptions;


use Mallto\Tool\Exception\HttpException;

class UserAuthExistException extends HttpException
{
    public function __construct(
        $userId = null,
        $message = "用户已经注册成功,请刷新重试",
        $statusCode = 422
    ) {
        \Log::warning("用户授权方式已存在:".$userId);

        parent::__construct($statusCode, $message, 4104);
    }
}
