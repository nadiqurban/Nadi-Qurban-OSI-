<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Header light/dark toggle — stored per user. */
class ThemeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['theme' => ['required', 'in:light,dark']]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['theme' => $data['theme']])->save();

        return response()->json(['theme' => $user->theme]);
    }
}
