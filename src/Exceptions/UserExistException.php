<?php
/**
 * Created by PhpStorm.
 * User: never615
 * Date: 12/07/2017
 * Time: 3:03 PM
 */

namespace Mallto\User\Exceptions;


use Exception;
use Mallto\Tool\Exception\ResourceException;

class UserExistException extends ResourceException
{
    public function __construct($message = null, $errors = null, Exception $previous = null, $headers = [], $code = 0)
    {
        parent::__construct($message ?: "用户已经存在", $errors, $previous, $headers, $code);
    }
}
