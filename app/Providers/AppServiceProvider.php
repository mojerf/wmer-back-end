<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use App\Support\ContentCache;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Catch N+1 queries and silent attribute mistakes while developing and testing.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Anything that appears in the cached public lists invalidates them when it changes.
        foreach ([Work::class, Post::class, Product::class, Comment::class] as $model) {
            $model::saved(fn () => ContentCache::bump());
            $model::deleted(fn () => ContentCache::bump());
        }

        Gate::define('manage-content', fn (User $user): bool => $user->isAdmin());

        // The OpenAPI docs (/docs/api) are open locally and visible to administrators elsewhere.
        Gate::define('viewApiDocs', fn (?User $user): bool => $this->app->isLocal() || (bool) $user?->isAdmin());

        // Reset links open the Angular app, which then calls POST /reset-password.
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token): string => rtrim(config('app.frontend_url'), '/')
            .'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset()));

        // The e-mailed link opens the Angular app with the signature parameters; the app forwards them to
        // POST /email/verify/{id}/{hash}, whose `signed` middleware checks them.
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $signed = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
            );
            parse_str((string) parse_url($signed, PHP_URL_QUERY), $query);

            return rtrim(config('app.frontend_url'), '/').'/verify-email?'.http_build_query([
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'expires' => $query['expires'] ?? null,
                'signature' => $query['signature'] ?? null,
            ]);
        });

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(3)
            ->by((string) (Auth::id() ?? $request->ip())));
        RateLimiter::for('payments', fn (Request $request) => Limit::perMinute(10)
            ->by((string) (Auth::id() ?? $request->ip())));
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(10)
            ->by((string) (Auth::id() ?? $request->ip())));
    }
}
