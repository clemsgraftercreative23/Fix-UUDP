<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Ixudra\Curl\Facades\Curl;
use App\Api;
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
        $auth = Api::where('id',1)->first();
        $curl = Curl::to("https://zeus.accurate.id/accurate/api/department/list.do")
                    ->withHeaders([
                        'Accept: application/json',
                        'Authorization: Bearer '.$auth->token,
                        'X-Session-ID: '.$auth->session
                    ])
                    ->returnResponseObject()
                    ->get();
        if(!$curl || $curl->status > 301) {
           \View::share('accurate', [
            'status' => false
           ]);
        } else {
            \View::share('accurate', [
                'status' => true
            ]);
        }

      if(config('app.env') === 'production') {
          \URL::forceScheme('https');
      }
    }
}
