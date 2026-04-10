<?php

namespace App\Providers;

use App\Repositories\AgencyManagerRepository;
use App\Repositories\AgencyManagerRepositoryInterface;
use App\Repositories\CustomerRepository;
use App\Repositories\CustomerRepositoryInterface;
use App\Repositories\EmployeeRepository;
use App\Repositories\EmployeeRepositoryInterface;
use App\Repositories\SolarCompanyManagerRepository;
use App\Repositories\SolarCompanyManagerRepositoryInterface;
use App\Repositories\SystemAdminRepository;
use App\Repositories\SystemAdminRepositoryInterface;
use App\Repositories\TokenRepository;
use App\Repositories\TokenRepositoryInterface;
use App\Services\AgencyManagerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SystemAdminRepositoryInterface::class, SystemAdminRepository::class);
        $this->app->bind(AgencyManagerRepositoryInterface::class, AgencyManagerRepository::class);
        $this->app->bind(AgencyManagerService::class, function ($app) {
            return new AgencyManagerService(
                $app->make(AgencyManagerRepositoryInterface::class),
                $app->make(TokenRepositoryInterface::class)
            );
        });
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(SolarCompanyManagerRepositoryInterface::class, SolarCompanyManagerRepository::class);
        $this->app->bind(TokenRepositoryInterface::class, TokenRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
