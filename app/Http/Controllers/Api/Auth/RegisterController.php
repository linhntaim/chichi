<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\ModelApiController;
use App\Http\Requests\Request;
use App\ModelRepositories\Base\IUserRepository;
use App\ModelRepositories\UserRepository;
use App\ModelResources\UserAccountResource;
use App\Utils\SocialLogin;

/**
 * Class RegisterController
 * @package App\Http\Controllers\Api\Auth
 * @property IUserRepository $modelRepository
 */
abstract class RegisterController extends ModelApiController
{
    protected function modelRepositoryClass()
    {
        return $this->getUserRepositoryClass();
    }

    protected function modelResourceClass()
    {
        return $this->getUserResourceClass();
    }

    /**
     * @return string
     */
    protected function getUserRepositoryClass()
    {
        return UserRepository::class;
    }

    /**
     * @return string
     */
    protected function getUserResourceClass()
    {
        return UserAccountResource::class;
    }

    public function store(Request $request)
    {
        if (SocialLogin::getInstance()->enabled()) {
            if ($request->has('_social')) {
                return $this->registerSocially($request);
            }
        }
        return $this->register($request);
    }

    public function register(Request $request)
    {
        return $this->responseFail();
    }

    protected function registerSociallyValidatedRules()
    {
        return [
            'email' => 'nullable|sometimes|max:255',
            'provider' => 'required|max:255',
            'provider_id' => 'required|max:255',
        ];
    }

    protected function registerSociallyExecuted(Request $request)
    {
        return $this->modelRepository->createWithAttributesFromSocial([
            'email' => $request->input('email'),
        ], [
            'provider' => $request->input('provider'),
            'provider_id' => $request->input('provider_id'),
        ]);
    }

    public function registerSocially(Request $request)
    {
        $this->validated($request, $this->registerSociallyValidatedRules());

        return $this->responseModel($this->registerSociallyExecuted($request));
    }

    // TODO:

    // TODO
}
