<?php 

namespace Prettus\RequestLogger\Providers;

use Illuminate\Support\ServiceProvider;
use Prettus\RequestLogger\Helpers\Benchmarking;
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
        $this->publishes([
            __DIR__ . '/../../../resources/config/request-logger.php' => config_path('request-logger.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../../resources/config/request-logger.php', 'request-logger'
        );
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
            $this->app->make('log')->extend('http_requests', function ($app, array $config) {
                $channel = new HttpRequestsLogChannel($app);
                return $channel($config);
            });
        }

        $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
        $kernel->prependMiddleware(\Prettus\RequestLogger\Middlewares\ResponseLoggerMiddleware::class);
    }

}
