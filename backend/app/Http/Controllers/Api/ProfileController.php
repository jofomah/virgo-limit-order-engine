<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileController extends Controller
{
    public function me(Request $request, UserRepository $userRepository): JsonResource
    {
        $user = $userRepository->withRelations(['assets'])
            ->findBy($request->user()->id);

        return new UserProfileResource($user);
    }
}
