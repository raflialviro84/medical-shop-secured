<?php

namespace CryptographicSession;

use CryptographicSession\Console\InstallCommand;
use CryptographicSession\Contracts\ProofVerifierInterface;
use CryptographicSession\Contracts\SessionBindingManagerInterface;
use CryptographicSession\Http\Middleware\VerifyCryptographicSession;
use CryptographicSession\Listeners\BindSessionOnLogin;
use CryptographicSession\Listeners\RevokeSessionOnLogout;
use CryptographicSession\Services\ProofVerifier;
use CryptographicSession\Services\SessionBindingManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CryptographicSessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cryptographic-session.php',
            'cryptographic-session'
        );

        $this->app->singleton(ProofVerifierInterface::class, function ($app) {
            $clockSkew = (int) config('cryptographic-session.clock_skew_seconds', 60);

            return new ProofVerifier($clockSkew);
        });

        // Alias class langsung ke singleton interface
        $this->app->singleton(ProofVerifier::class, function ($app) {
            return $app->make(ProofVerifierInterface::class);
        });

        $this->app->singleton(SessionBindingManagerInterface::class, function ($app) {
            return new SessionBindingManager();
        });

        $this->app->singleton(SessionBindingManager::class, function ($app) {
            return $app->make(SessionBindingManagerInterface::class);
        });
    }

    public function boot(): void
    {
        // 1. Routes
        if (config('cryptographic-session.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        // 2. Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 3. Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'csb');

        // 4. Middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('cryptographic.session', VerifyCryptographicSession::class);

        // 5. Auth Events Hooks
        if (config('cryptographic-session.auto_bind_on_login', true)) {
            Event::listen(Login::class, BindSessionOnLogin::class);
        }

        if (config('cryptographic-session.auto_revoke_on_logout', true)) {
            Event::listen(Logout::class, RevokeSessionOnLogout::class);
        }

        // 6. Blade Directive
        Blade::directive('cryptographicSession', function () {
            return "<?php echo view('csb::scripts')->render(); ?>";
        });

        // 7. Console & Publishables
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/cryptographic-session.php' => config_path('cryptographic-session.php'),
            ], 'csb-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'csb-migrations');

            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/cryptographic-session'),
            ], 'csb-assets');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/csb'),
            ], 'csb-views');
        }
    }
}

