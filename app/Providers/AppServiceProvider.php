<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureModels();
        $this->configureRateLimiting();
        $this->configureApiDocs();

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(12)->mixedCase()->numbers()->uncompromised()
            : Password::min(8));
    }

    private function configureModels(): void
    {
        // Lazy loading, silently discarded attributes and missing attributes throw outside production.
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::transliterate(Str::lower($request->string('email')->value())).'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    private function configureApiDocs(): void
    {
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->secure(SecurityScheme::apiKey('cookie', config()->string('session.cookie')));
        });
    }
}
