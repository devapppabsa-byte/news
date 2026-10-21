<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AI\AIConfigRepository;
use App\Services\AI\AIProviderRegistry;
use App\Services\AI\Providers\AnthropicAnalysisProvider;
use App\Services\AI\Providers\OpenAIAnalysisProvider;
use App\Services\AI\Providers\OpenRouterAnalysisProvider;
use App\Services\Search\CredentialRepository;
use App\Services\Search\ProviderRegistry;
use App\Services\Search\Providers\BraveSearchProvider;
use App\Services\Search\Providers\DuckDuckGoProvider;
use App\Services\Search\Providers\GoogleCseProvider;
use App\Services\Search\Providers\OpenRouterSearchProvider;
use App\Services\Search\Providers\RssFeedProvider;
use App\Services\Search\Providers\TavilySearchProvider;
use App\Services\Settings\SettingsRepository;
use App\Support\PromptSanitizer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CredentialRepository::class);

        $this->app->singleton(SettingsRepository::class);

        $this->app->singleton(PromptSanitizer::class, function (): PromptSanitizer {
            return new PromptSanitizer((int) config('novedades.prompt_max_length', 2000));
        });

        $this->app->singleton(AIConfigRepository::class);

        $this->app->singleton(AIProviderRegistry::class, function ($app): AIProviderRegistry {
            return new AIProviderRegistry([
                $app->make(OpenAIAnalysisProvider::class),
                $app->make(OpenRouterAnalysisProvider::class),
                $app->make(AnthropicAnalysisProvider::class),
            ], $app->make(AIConfigRepository::class));
        });

        $this->app->singleton(ProviderRegistry::class, function ($app): ProviderRegistry {
            return new ProviderRegistry([
                $app->make(RssFeedProvider::class),
                $app->make(BraveSearchProvider::class),
                $app->make(TavilySearchProvider::class),
                $app->make(GoogleCseProvider::class),
                $app->make(DuckDuckGoProvider::class),
                $app->make(OpenRouterSearchProvider::class),
            ], $app->make(CredentialRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isAdmin() ? true : null;
        });
    }
}
