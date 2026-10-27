<?php
namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(filament()->getHomeUrl());
    }
}
