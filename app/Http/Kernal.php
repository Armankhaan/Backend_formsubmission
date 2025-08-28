'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // ok for SPA or token
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
