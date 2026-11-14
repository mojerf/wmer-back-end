<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use App\Support\ContentCache;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(10)
            ->by((string) (Auth::id() ?? $request->ip())));
    }
}
