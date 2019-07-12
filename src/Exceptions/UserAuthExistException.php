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
        $message = "用户已经注册成功,请刷新重试",
        $statusCode = "422"
    ) {
        $this->errCode = "4104";
        \Log::warning("注册并发/重复提交导致报错");

        parent::__construct($statusCode, $message);
    }
}
