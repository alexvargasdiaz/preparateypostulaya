<?php

namespace Modules\UserManagement\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class UserManagementServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'UserManagement';
    protected string $nameLower = 'usermanagement';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
