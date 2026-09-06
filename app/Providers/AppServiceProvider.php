<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Services\Accurate\AccurateApiTokenClient;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
    

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Console commands (artisan, queue workers, tests) must not block on a
        // live Accurate API call during boot — it previously hung every CLI
        // invocation and made the test suite unrunnable.
        if ($this->app->runningInConsole() || $this->app->environment('testing')) {
            View::share('accurate', [
                'status' => false
            ]);
        } else {
            // Cached + a short single-attempt timeout: this status ping is
            // purely cosmetic (header "Accurate Status: Online/Offline"
            // badge) but used to run request()'s full multi-signature-mode
            // retry loop on every single page load with a 60s timeout per
            // attempt — when Accurate was slow/unreachable that could exceed
            // PHP's own max_execution_time and fatal-error every page.
            $status = Cache::remember('accurate_status_online', now()->addMinutes(2), function () {
                return (new AccurateApiTokenClient())->quickStatusCheck();
            });
            View::share('accurate', [
                'status' => $status
            ]);
        }

      if(config('app.env') === 'production') {
          URL::forceScheme('https');
      }
    }
}
