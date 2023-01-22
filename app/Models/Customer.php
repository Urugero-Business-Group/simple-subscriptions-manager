<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'card_number',
    ];

    public function subscriptions()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function scopeWithSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }]);
    }

    public function scopeWithTrashedSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withTrashed();
        }]);
    }

    public function scopeWithoutSubscriptions(Builder $query)
    {
        return $query->doesntHave('subscriptions');
    }

    public function scopeWithActiveSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNull('ended_at');
        }]);
    }

    public function scopeWithInactiveSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull('ended_at');
        }]);
    }

    public function scopeWithDeletedSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->onlyTrashed();
        }]);
    }

    public function scopeWithActiveAndInactiveSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }]);
    }

    public function scopeWithActiveAndDeletedSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withTrashed();
        }]);
    }

    public function scopeWithInactiveAndDeletedSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull('ended_at');
        }]);
    }

    public function scopeWithActiveInactiveAndDeletedSubscriptions(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withTrashed();
        }]);
    }

    public function scopeWithActiveSubscriptionsAndSubscription(Builder $query)
    {
        return $query->with(['subscriptions' => function ($query) {
            $query->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNull('ended_at');
        }, 'subscriptions.subscription']);
    }

    // names
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
