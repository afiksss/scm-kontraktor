<?php

namespace App\Providers;

use App\Models\User;
use App\Services\NotificationService;
use App\Support\Access;
use App\Support\Fmt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        // Izin berbasis role dari config/scm.php -> dipakai lewat middleware "can:" dan @can.
        // Daftar role dibaca saat dicek (bukan saat boot) agar perubahan pengaturan langsung berlaku.
        foreach (array_keys(config('scm.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->is_active && in_array($user->role, Access::roles($permission), true));
        }

        View::share('fmt', new Fmt());

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('notifications', $user ? app(NotificationService::class)->for($user) : []);
        });
    }
}
