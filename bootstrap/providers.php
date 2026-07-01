<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\EmployeePanelProvider;
use App\Providers\Filament\SupervisorPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    SupervisorPanelProvider::class,
    EmployeePanelProvider::class,
];
