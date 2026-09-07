<?php

use App\Http\Controllers\SeoLandingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PROGRAMMATIC SEO ROUTES — must stay AFTER all application routes.
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SeoLandingController::class, 'sitemapIndex'])->name('seo.sitemap.index');
Route::get('/sitemaps/{group}.xml', [SeoLandingController::class, 'sitemapChild'])->name('seo.sitemap.child');
Route::get('/robots.txt', [SeoLandingController::class, 'robots'])->name('seo.robots');
Route::get('/cari-solusi', [SeoLandingController::class, 'finder'])->name('seo.finder');
Route::post('/seo/cta-click', [SeoLandingController::class, 'click'])->name('seo.cta.click');

Route::get('/{path}', [SeoLandingController::class, 'show'])
    ->where('path', '^(?!api|build|docs|storage|livewire|up|offline|manifest\.webmanifest|sw\.js|icons|favicon\.ico)[A-Za-z0-9\-_\/]+$')
    ->name('seo.landing');
