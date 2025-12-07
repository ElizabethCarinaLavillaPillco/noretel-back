<?php

namespace Modules\Network\Services;

use Modules\Network\Entities\Router;
use Modules\Network\Entities\RouterLog;
use Modules\Network\Entities\ServiceRequest;
use Modules\Network\Adapters\HuaweiAdapter;
use Modules\Network\Adapters\MikroTikAdapter;
use Modules\Network\Adapters\CiscoAdapter;
use Illuminate\Support\Facades\Log;
use Exception;

class RouterService
{
    /**
     * Obtener el adaptador correcto según la marca del router
     */
    protected function getAdapter(Router $router)
    {
        return match($router->brand) {
            'Huawei' => new HuaweiAdapter($router),
            'MikroTik' => new MikroTikAdapter($router),
            'Cisco' => new CiscoAdapter($router),
            default => throw new Exception("Marca de router '{$router->brand}' no soportada")
        };
    }

    /**
     * Reiniciar router
     */
    public function reboot(Router $router, $userId = null, ServiceRequest $serviceRequest = null)
    {
        $startTime = microtime(true);

        // Crear log inicial
        $log = RouterLog::create([
            'router_id' => $router->id,
            'action' => 'reboot',
            'status' => 'initiated',
            'description' => 'Reinicio de router solicitado',
            'user_id' => $userId ?? auth()->id(),
            'service_request_id' => $serviceRequest?->id,
            'is_automated' => $serviceRequest?->is_automated ?? false,
            'metrics_before' => [
                'cpu_usage' => $router->cpu_usage,
                'memory_usage' => $router->memory_usage,
                'connected_clients' => $router->connected_clients,
                'uptime' => $router->uptime,
            ]
        ]);

        try {
            $adapter = $this->getAdapter($router);
            
            // Ejecutar reinicio
            $result = $adapter->reboot();
            
            // Actualizar router
            $router->update([
                'last_reboot' => now(),
                'status' => 'active'
            ]);

            // Actualizar log con éxito
            $executionTime = (microtime(true) - $startTime) * 1000;
            $log->update([
                'status' => 'success',
                'response_data' => $result,
                'execution_time' => $executionTime,
            ]);

            Log::info("Router {$router->name} reiniciado exitosamente", [
                'router_id' => $router->id,
                'execution_time' => $executionTime
            ]);

            return [
                'success' => true,
                'message' => 'Router reiniciado exitosamente. El servicio se restablecerá en 2-3 minutos.',
                'log_id' => $log->id,
                'execution_time' => $executionTime
            ];

        } catch (Exception $e) {
            // Actualizar log con error
            $executionTime = (microtime(true) - $startTime) * 1000;
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'execution_time' => $executionTime,
            ]);

            // Actualizar router a estado de error
            $router->update(['status' => 'error']);

            Log::error("Error al reiniciar router {$router->name}", [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $log->id
            ];
        }
    }

    /**
     * Obtener estado del router
     */
    public function getStatus(Router $router)
    {
        try {
            $adapter = $this->getAdapter($router);
            $status = $adapter->getStatus();

            // Actualizar métricas del router
            $router->update([
                'cpu_usage' => $status['cpu_usage'] ?? null,
                'memory_usage' => $status['memory_usage'] ?? null,
                'uptime' => $status['uptime'] ?? null,
                'connected_clients' => $status['connected_clients'] ?? 0,
                'last_health_check' => now(),
                'status' => 'active'
            ]);

            // Crear snapshot de métricas
            \Modules\Network\Entities\RouterMetricsHistory::createSnapshot($router);

            return [
                'success' => true,
                'data' => $status
            ];

        } catch (Exception $e) {
            $router->update(['status' => 'offline']);

            Log::error("Error al obtener estado del router {$router->name}", [
                'router_id' => $router->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ajustar límite de ancho de banda para un cliente
     */
    public function adjustBandwidth(Router $router, $customerId, $downloadLimit, $uploadLimit)
    {
        $log = RouterLog::create([
            'router_id' => $router->id,
            'action' => 'bandwidth_adjustment',
            'status' => 'initiated',
            'description' => "Ajuste de ancho de banda para cliente ID: {$customerId}",
            'user_id' => auth()->id(),
            'request_data' => [
                'customer_id' => $customerId,
                'download_limit' => $downloadLimit,
                'upload_limit' => $uploadLimit
            ]
        ]);

        try {
            $adapter = $this->getAdapter($router);
            
            // Obtener configuración del cliente en el router
            $customerConfig = $router->customers()
                ->where('customer_id', $customerId)
                ->firstOrFail();

            // Ejecutar ajuste
            $result = $adapter->setBandwidthLimit(
                $customerConfig->pivot->pppoe_username,
                $downloadLimit,
                $uploadLimit
            );

            // Actualizar en pivot table
            $router->customers()->updateExistingPivot($customerId, [
                'bandwidth_limit_down' => $downloadLimit,
                'bandwidth_limit_up' => $uploadLimit,
            ]);

            $log->update([
                'status' => 'success',
                'response_data' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Ancho de banda ajustado exitosamente',
                'log_id' => $log->id
            ];

        } catch (Exception $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Suspender servicio de un cliente
     */
    public function suspendCustomer(Router $router, $customerId)
    {
        try {
            $adapter = $this->getAdapter($router);
            
            $customerConfig = $router->customers()
                ->where('customer_id', $customerId)
                ->firstOrFail();

            // Suspender en el router
            $result = $adapter->disableClient($customerConfig->pivot->pppoe_username);

            // Actualizar estado en pivot table
            $router->customers()->updateExistingPivot($customerId, [
                'connection_status' => 'suspended',
            ]);

            RouterLog::create([
                'router_id' => $router->id,
                'action' => 'client_disconnected',
                'status' => 'success',
                'description' => "Cliente ID {$customerId} suspendido",
                'user_id' => auth()->id(),
                'response_data' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Servicio suspendido exitosamente'
            ];

        } catch (Exception $e) {
            Log::error("Error al suspender cliente en router", [
                'router_id' => $router->id,
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Activar servicio de un cliente
     */
    public function activateCustomer(Router $router, $customerId)
    {
        try {
            $adapter = $this->getAdapter($router);
            
            $customerConfig = $router->customers()
                ->where('customer_id', $customerId)
                ->firstOrFail();

            // Activar en el router
            $result = $adapter->enableClient($customerConfig->pivot->pppoe_username);

            // Actualizar estado en pivot table
            $router->customers()->updateExistingPivot($customerId, [
                'connection_status' => 'active',
                'last_connection' => now(),
            ]);

            RouterLog::create([
                'router_id' => $router->id,
                'action' => 'client_connected',
                'status' => 'success',
                'description' => "Cliente ID {$customerId} activado",
                'user_id' => auth()->id(),
                'response_data' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Servicio activado exitosamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar salud de todos los routers activos
     */
    public function healthCheckAll()
    {
        $routers = Router::active()->get();
        $results = [];

        foreach ($routers as $router) {
            $results[$router->id] = $this->getStatus($router);
        }

        return $results;
    }

    /**
     * Obtener estadísticas del router
     */
    public function getStatistics(Router $router, $period = '24h')
    {
        $hours = match($period) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $metrics = $router->metricsHistory()
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->orderBy('recorded_at', 'asc')
            ->get();

        return [
            'period' => $period,
            'metrics' => $metrics,
            'summary' => [
                'avg_cpu' => $metrics->avg('cpu_usage'),
                'max_cpu' => $metrics->max('cpu_usage'),
                'avg_memory' => $metrics->avg('memory_usage'),
                'max_memory' => $metrics->max('memory_usage'),
                'avg_clients' => $metrics->avg('connected_clients'),
                'max_clients' => $metrics->max('connected_clients'),
            ]
        ];
    }
}
