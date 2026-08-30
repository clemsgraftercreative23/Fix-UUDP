<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
            $accurateClient = new AccurateApiTokenClient();
            $curl = $accurateClient->request('GET', '/accurate/api/department/list.do');
            if (!($curl['ok'] ?? false)) {
                View::share('accurate', [
                    'status' => false
                ]);
            } else {
                View::share('accurate', [
                    'status' => true
                ]);
            }
        }

      if(config('app.env') === 'production') {
          URL::forceScheme('https');
      }
    }
}
