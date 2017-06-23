<?php
namespace Mallto\User\Controller\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mallto\User\Domain\UserUsecase;


/**
 * Created by PhpStorm.
 * User: never615
 * Date: 19/04/2017
 * Time: 7:01 PM
 */
class UserController extends Controller
{
    /**
     * @var UserUsecase
     */
    private $userUsecase;

    /**
     * RegisterController constructor.
     *
     * @param UserUsecase $userUsecase
     */
    public function __construct(UserUsecase $userUsecase)
    {
        $this->userUsecase = $userUsecase;
    }

    /**
     * 请求用户信息
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    public function show()
    {
        $user = Auth::guard("api")->user();

        return $this->userUsecase->getUserInfo($user->id);
    }

//    /**
//     * 更新用户信息
//     *
//     * @param Request $request
//     */
//    public function update(Request $request)
//    {
//        \Log::info($request->all());
//    }

}
