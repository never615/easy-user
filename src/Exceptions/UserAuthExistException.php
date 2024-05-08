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
use Mallto\User\Data\UserAuth;

class UserAuthExistException extends HttpException
{

    /**
     * @var UserAuth|null
     */
    private $userAuth;


    /**
     * UserAuthExistException constructor.
     *
     * @param UserAuth   $userAuth 已经存在的UserAuth对象
     * @param string $message
     * @param int    $statusCode
     */
    public function __construct(
        $userAuth = null,
        $message = '用户已经注册成功,请刷新重试',
        $statusCode = 422
    ) {
        parent::__construct($statusCode, $message, 4104);
        $this->userAuth = $userAuth;
    }


    /**
     * @return UserAuth|null
     */
    public function getUserAuth(): ?UserAuth
    {
        return $this->userAuth;
    }
}
