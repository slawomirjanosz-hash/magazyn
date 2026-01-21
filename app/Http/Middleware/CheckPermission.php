<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Musisz się zalogować');
        }

        $user = auth()->user();

        // Tylko super admin ma dostęp do wszystkiego
        if ($user->email === 'proximalumine@gmail.com') {
            return $next($request);
        }

        // Sprawdzenie uprawnień
        $permissions = [
            'view_catalog' => 'can_view_catalog',
            'add' => 'can_add',
            'remove' => 'can_remove',
            'orders' => 'can_orders',
            'settings' => 'can_settings',
        ];

        if (isset($permissions[$permission]) && $user->{$permissions[$permission]}) {
            return $next($request);
        }

        return response()->view('errors.403', ['message' => 'Brak uprawnień do tej funkcji'], 403);
    }
}
