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

class UserExistException extends HttpException
{

    public function __construct(
        $message = "用户已经注册成功,请刷新重试",
        $statusCode = 422
    ) {
        parent::__construct($statusCode, $message, 4104);
    }
}
