<?php

namespace Mallto\User\Controller;


use Encore\Admin\Auth\Database\Report;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;

class TestController extends Controller
{

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Request $request)
    {
//        Log::info($request->getBaseUrl());
//        Log::info($request->getBasePath());
//        Log::info($request->getHost());
//        Log::info($request->getHttpHost());
//        Log::info($request->getUri());
//        Log::info($request->getRequestUri());
//        Log::info($request->getMethod());
//        Log::info(Request::root());
//
//        echo "haha";

        return "haha";

    }


}
