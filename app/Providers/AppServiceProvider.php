<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('es');
        Paginator::defaultView('partials.paginacion');

        // @dinero($valor)  →  $1,234.50
        Blade::directive('dinero', fn ($e) => "<?php echo '$'.number_format((float) ({$e}), 2); ?>");

        // @cant($valor)  →  3  o  1.5
        Blade::directive('cant', fn ($e) => "<?php echo rtrim(rtrim(number_format((float) ({$e}), 3, '.', ','), '0'), '.'); ?>");
    }
}
