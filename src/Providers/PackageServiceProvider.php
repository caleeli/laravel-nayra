<?php

namespace ProcessMaker\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Laravel\Contracts\RequestRepositoryInterface;
use ProcessMaker\Laravel\Nayra\JobManager;
use ProcessMaker\Laravel\Nayra\Manager;
use ProcessMaker\Laravel\Repositories\InstanceRepository;
use ProcessMaker\Laravel\Repositories\RequestRepository;
use ProcessMaker\Laravel\Repositories\TokenRepository;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;

class PackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(
            'nayra.manager',
            function () {
                return new Manager(app(RequestRepositoryInterface::class));
            }
        );
        $this->app->singleton(RequestRepositoryInterface::class, function () {
            return new RequestRepository();
        });
        $this->app->singleton(JobManagerInterface::class, function () {
            return new JobManager();
        });
        $this->app->singleton(ExecutionInstanceRepositoryInterface::class, function () {
            return new InstanceRepository(app(RequestRepositoryInterface::class));
        });
        $this->app->singleton(TokenRepositoryInterface::class, function () {
            return new TokenRepository();
        });
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
