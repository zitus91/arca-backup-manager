<?php

namespace App\Trait;

use App\Models\Scopes\OwnedByUserScope;
use Illuminate\Support\Facades\Auth;

/**
 * Adds per-user ownership: a global scope that filters to the authenticated
 * user, and auto-stamps user_id on rows created during a web request.
 */
trait OwnedByUser
{
    protected static function bootOwnedByUser(): void
    {
        static::addGlobalScope(new OwnedByUserScope);

        static::creating(function ($model) {
            if (empty($model->user_id) && Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }
}
