<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** Land on the first sidebar module the user can view (Vendor PIC has no Dashboard). */
    public function __invoke(Request $request, Navigation $navigation): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAgent()) {
            return redirect()->route('agent.portal');
        }

        $first = $navigation->for($user)->flatMap(fn (array $group) => $group['items'])->first();

        return redirect($first['href'] ?? route('settings.profile'));
    }
}
