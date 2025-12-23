<?php

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Network\Entities\Router;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;

class SSHController extends Controller
{
    /**
     * Mostrar terminal SSH
     */
    public function show(Router $router)
    {
        return view('network::ssh-terminal', compact('router'));
    }

    /**
     * Ejecutar comando SSH
     */
    public function execute(Request $request, Router $router)
    {
        $request->validate([
            'command' => 'required|string'
        ]);

        try {
            // Obtener credenciales
            $credentials = $router->credentials ?? [];
            $username = $credentials['username'] ?? 'admin';
            $password = $credentials['password'] ?? '';

            // Conectar por SSH
            $ssh = new SSH2($router->ip_address);

            if (!$ssh->login($username, $password)) {
                throw new \Exception('Error de autenticación SSH');
            }

            // Ejecutar comando
            $command = $request->command;
            $output = $ssh->exec($command);

            // Cerrar conexión
            $ssh->disconnect();

            // Registrar en logs
            Log::info("SSH command executed on router {$router->name}", [
                'router_id' => $router->id,
                'command' => $command,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'output' => $output ?: 'Comando ejecutado correctamente (sin salida)'
            ]);

        } catch (\Exception $e) {
            Log::error("SSH command failed on router {$router->name}", [
                'router_id' => $router->id,
                'command' => $request->command,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
