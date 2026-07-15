<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts queries to rows owned by the authenticated user.
 *
 * Only active when a user is authenticated (web/Livewire requests). In console
 * and queue contexts there is no authenticated user, so the scope is a no-op
 * and background work (scheduler, recovery, jobs) sees every owner's rows.
 */
class OwnedByUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $builder->where($model->getTable().'.user_id', Auth::id());
        }
    }
}
