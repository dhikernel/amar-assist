<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (app()->environment('local')) {
                return true;
            }

            return in_array(
                optional($user)->email,
                array_filter(explode(',', (string) env('HORIZON_ADMIN_EMAILS'))),
                true
            );
        });
    }
}
