<?php

namespace App\Models\Scopes;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Defense in depth: while a Vendor PIC is signed in, every Order query only
 * returns orders allocated to their own vendor — list screens, detail modals,
 * route-model binding and PDF exports alike. Staff, API clients, queued jobs
 * and the public tracking page (no signed-in user) are unaffected.
 *
 * @implements Scope<Order>
 */
class VendorPicOrderScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        if ($user instanceof User && $user->isVendorPic()) {
            $builder->whereHas('allocation', fn (Builder $a) => $a->where('vendor_id', (int) $user->vendor_id));
        }
    }
}
