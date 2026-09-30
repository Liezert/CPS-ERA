<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            // Tanpa halaman login Filament: tamu diarahkan ke /login aplikasi (Bahasa Indonesia),
            // dan logout dari panel juga kembali ke sana.
            ->brandName('CPS ERA Admin')
            ->brandLogo(asset('images/cps-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/cps-logo.png'))
            ->font('Inter')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            // Grup sama persis dengan kartu "Kelola Data" di dashboard admin (Dashboard::adminShortcuts()).
            // Advance (pengaturan sekali setel: Google Drive, Target KPI) selalu terakhir dan tertutup.
            ->navigationGroups([
                'Learning & Pengembangan',
                'Knowledge & Video',
                'SDM & Laporan',
                NavigationGroup::make('Advance')->collapsed(),
            ])
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
            // Pemulihan isian form "Create" setelah refresh/koneksi putus (lihat partials/form-draft).
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => view('partials.form-draft')->render())
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                // Latar soft gradient hijau yang sama dengan halaman utama CPS ERA.
                fn (): HtmlString => new HtmlString(app(Vite::class)('resources/css/background.css')->toHtml()),
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
                EnsurePasswordIsChanged::class,
            ]);
    }
}
