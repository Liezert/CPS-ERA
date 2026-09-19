<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Filament\Widgets\AdminOverviewWidget;
use App\Filament\Widgets\QuickActionsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('CPS ERA Admin')
            ->brandLogo(asset('images/cps-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/cps-logo.png'))
            ->font('Inter')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => [
                    50 => '#e8f5ec',
                    100 => '#c8e6d1',
                    200 => '#96d2ac',
                    300 => '#5eb980',
                    400 => '#2d9e5b',
                    500 => '#0B7840',
                    600 => '#085C30',
                    700 => '#064624',
                    800 => '#05371c',
                    900 => '#032513',
                    950 => '#011309',
                ],
                'gray' => Color::Zinc,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminOverviewWidget::class,
                QuickActionsWidget::class,
                AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <style>
                        :root {
                            --font-family-sans: "Inter", system-ui, sans-serif;
                        }
                        body {
                            font-family: var(--font-family-sans);
                        }
                        .fi-sidebar {
                            border-right: 1px solid #E4E4E7 !important;
                            background-color: #FFFFFF !important;
                        }
                        .fi-topbar {
                            border-bottom: 1px solid #E4E4E7 !important;
                            background-color: #FFFFFF !important;
                        }
                        .fi-section, .fi-widget {
                            box-shadow: none !important;
                            border: 1px solid #E4E4E7 !important;
                            border-radius: 8px !important;
                        }
                        .fi-badge {
                            border-radius: 2px !important;
                        }
                    </style>
                ')
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                // Halaman review CAPA memakai komponen form employee (Tailwind v3 app),
                // dimuat sebagai CSS ter-scope `.capa-scope` agar tidak bentrok dengan Filament.
                fn (): HtmlString => new HtmlString(
                    '<link rel="preconnect" href="https://fonts.bunny.net">'
                    .'<link href="https://fonts.bunny.net/css?family=ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />'
                    .app(Vite::class)('resources/css/capa-admin.css')->toHtml()
                ),
                scopes: ViewBaIncident::class,
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
