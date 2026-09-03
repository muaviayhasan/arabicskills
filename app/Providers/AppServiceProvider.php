<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!app()->runningInConsole()) {
            if (Schema::hasTable('options')) {
                $options = DB::table('options')->get();

                // set all data from Option::class to config
                foreach ($options as $option) {
                    config()->set("options.{$option->key}", $option->value);
                }
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
