<?php 

namespace Prettus\RequestLogger\Providers;

use Illuminate\Support\ServiceProvider;
use Prettus\RequestLogger\HttpRequestsLogChannel;

/**
 * Class LoggerServiceProvider
 * @package Prettus\RequestLogger\Providers
 */
class LoggerServiceProvider extends ServiceProvider 
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app('events')->listen('router.before', function() {
            Benchmarking::start('application');
        });

        app('events')->listen('router.after', function() {
            Benchmarking::end('application');
        });

        $app = $this->app;

        // Add a requests log channel for Laravel 5.6+
        if (version_compare($app::VERSION, '5.6') >= 0) {
            $app->make('log')->extend('requests_log', function ($app, array $config) {
                $channel = new HttpRequestsLogChannel($app);
                return $channel($config);
            });
        } else {
            throw new \LogicException("You should use Laravel >= 5.6 for this module to work");
        }

        $kernel = $app->make('Illuminate\Contracts\Http\Kernel');
        $kernel->prependMiddleware(\Prettus\RequestLogger\Middlewares\ResponseLoggerMiddleware::class);
    }

}
