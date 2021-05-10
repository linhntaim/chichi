<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Request;

class IndexController extends ApiController
{
    public function index(Request $request)
    {
        $this->abort404();
    }
}