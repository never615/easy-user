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
        $message = "用户已经注册,请刷新成功",
        $statusCode = "422"
    ) {
        $this->errCode = "4104";
        parent::__construct($statusCode, $message);
    }
}
