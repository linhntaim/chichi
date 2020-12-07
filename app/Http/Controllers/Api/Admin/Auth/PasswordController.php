<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Api\Auth\PasswordController as BasePasswordController;
use App\Http\Requests\Request;
use App\ModelRepositories\AdminRepository;
use App\Models\Admin;
use App\Utils\ConfigHelper;
use Closure;

class PasswordController extends BasePasswordController
{
    protected function brokerSendResetLink(array $credentials, Closure $callback = null)
    {
        return parent::brokerSendResetLink($credentials, $callback ? $callback : function ($user, $token) {
            (new AdminRepository())->model($user->id)->sendPasswordResetNotification($token);
        });
    }

    public function index(Request $request)
    {
        if (ConfigHelper::get('forgot_password_enabled.admin')) {
            return parent::index($request);
        }
        return $this->abort404();
    }

    public function store(Request $request)
    {
        if (ConfigHelper::get('forgot_password_enabled.admin')) {
            return parent::store($request);
        }
        return $this->abort404();
    }

    protected function resetValidatedRules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'string', sprintf('min:%d', Admin::MIN_PASSWORD_LENGTH), 'confirmed'],
        ];
    }
}
