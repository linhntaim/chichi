<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ModelApiController;
use App\Http\Requests\Request;
use App\ModelRepositories\DeviceRepository;
use App\Models\Device;

class DeviceController extends ModelApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->modelRepository = new DeviceRepository();
    }

    public function currentStore(Request $request)
    {
        $this->validated($request, [
            'provider' => 'nullable|sometimes|string|max:255',
            'secret' => 'nullable|sometimes|string|max:255',
        ]);

        $currentUser = $request->user();
        return $this->responseModel($this->modelRepository->save(
            $request->input('provider', Device::PROVIDER_BROWSER),
            $request->input('secret', ''),
            $request->getClientIps(),
            empty($currentUser) ? null : $currentUser->id
        ));
    }
}
