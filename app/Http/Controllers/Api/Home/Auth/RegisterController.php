<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Http\Controllers\Api\Home\Auth;

use App\Http\Controllers\Api\Auth\RegisterController as BaseRegisterController;
use App\Http\Requests\Request;
use App\ModelRepositories\UserRepository;
use App\ModelResources\UserAccountResource;

class RegisterController extends BaseRegisterController
{
    public function __construct()
    {
        parent::__construct();

        $this->modelRepository = new UserRepository();
        $this->setFixedModelResourceClass(
            UserAccountResource::class,
            $this->modelRepository->modelClass()
        );
    }

    public function registerSocially(Request $request)
    {
        $this->validated($request, [
            'email' => 'nullable|sometimes|max:255',
            'provider' => 'required|max:255',
            'provider_id' => 'required|max:255',
        ]);

        return $this->responseModel(
            $this->modelRepository->createWithAttributesFromSocial([
                'email' => $request->input('email'),
            ], [
                'provider' => $request->input('provider'),
                'provider_id' => $request->input('provider_id'),
            ])
        );
    }
}
