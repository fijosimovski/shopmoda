<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Setting;
use App\Services\Cart;
use App\Services\Promotions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One Cart object per request.
        $this->app->singleton(Cart::class);

        // Running promotions are loaded once per request and reused for every price.
        $this->app->singleton(Promotions::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates like "pre 2 sata" / "za 3 dana" in Serbian Latin (Carbon's plain "sr" is Cyrillic).
        \Illuminate\Support\Carbon::setLocale('sr_Latn');

        // Pagination links styled with Bootstrap 5 (we use Bootstrap from a CDN).
        Paginator::useBootstrapFive();

        // Password rules for register and profile (used as Password::defaults()).
        // Keep the "password" rule in public/js/validation.js in sync with this.
        Password::defaults(function () {
            return Password::min(8)->max(100)->letters()->mixedCase()->numbers();
        });

        // @money($price) in Blade prints e.g. "12.999 RSD"
        Blade::directive('money', function ($expression) {
            return "<?php echo e(\App\Support\Money::format((float) ($expression))); ?>";
        });

        // The navigation needs the categories, brands, Instagram link and the cart count on every page.
        View::composer('fixed.navigation', function ($view) {
            $view->with('navCategories', Category::orderBy('name')->get());
            // Only brands that have something to show, so the dropdown never leads to an empty list.
            $view->with('navBrands', Brand::whereHas('products', fn ($q) => $q->active())->orderBy('name')->get());
            $view->with('instagramUrl', Setting::getValue(Setting::INSTAGRAM_URL));
            $view->with('cartCount', app(Cart::class)->count());
        });

        // Footer: Instagram link and contact details from Admin > Settings.
        View::composer('fixed.footer', function ($view) {
            $view->with('footerInstagram', Setting::getValue(Setting::INSTAGRAM_URL));
            $view->with('footerPhone', Setting::getValue(Setting::CONTACT_PHONE));
            // Only the contact email set on purpose; never the order email / mail account it falls back to elsewhere.
            $view->with('footerEmail', Setting::getValue(Setting::CONTACT_EMAIL));
        });

        // Announcement bar under the navigation: "home" ones only on the homepage, "all" ones everywhere.
        View::composer('partials.announcements', function ($view) {
            $view->with('announcements', Announcement::forPage(request()->routeIs('home')));
        });
    }
}
