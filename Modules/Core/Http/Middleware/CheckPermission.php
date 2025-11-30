<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\PermissionService;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * CheckPermission constructor.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $module
     * @param string $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $module, $permission)
    {
        if (!Auth::check()) {
            return redirect()->route('core.auth.login');
        }

        $user = Auth::user();

        // ========================================
        // TEMPORAL: Permitir todo para super-admin
        // ========================================
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $context = [];

        // Si hay ruta con parámetro, agregarlo al contexto
        if ($request->route('id')) {
            $context['entity_id'] = $request->route('id');
        }

        // Primero verificamos si tiene permiso de gestión completa del módulo
        try {
            if ($this->permissionService->hasPermission(Auth::id(), 'manage', $module, $context)) {
                return $next($request);
            }

            // Si no tiene permiso de gestión, verificamos el permiso específico
            if (!$this->permissionService->hasPermission(Auth::id(), $permission, $module, $context)) {
                abort(403, 'No tiene permiso para acceder a este recurso.');
            }
        } catch (\Exception $e) {
            // Si falla PermissionService, permitir para super-admin
            if ($user->hasRole('super-admin')) {
                return $next($request);
            }
            abort(403, 'No tiene permiso para acceder a este recurso.');
        }

        return $next($request);
    }
}
