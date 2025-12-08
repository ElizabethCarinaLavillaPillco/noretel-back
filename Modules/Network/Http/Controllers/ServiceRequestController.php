<?php

namespace Modules\Network\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Network\Entities\ServiceRequest;
use Modules\Core\Entities\User;

class ServiceRequestController extends Controller
{
    /**
     * Mostrar lista de solicitudes de servicio
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::with(['customer', 'router', 'assignedTechnician']);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $serviceRequests = $query->latest()->paginate(20);

        // Obtener datos para filtros
        $statuses = ['pending', 'in_progress', 'completed', 'failed', 'cancelled'];
        $types = ['router_reboot', 'connection_issue', 'slow_speed', 'no_internet', 'other'];
        $priorities = ['low', 'medium', 'high', 'critical'];

        return view('network::service-requests.index', compact(
            'serviceRequests',
            'statuses',
            'types',
            'priorities'
        ));
    }

    /**
     * Mostrar detalles de una solicitud
     */
    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load([
            'customer.user',
            'router',
            'contract',
            'assignedTechnician',
            'routerLogs'
        ]);

        return view('network::service-requests.show', compact('serviceRequest'));
    }

    /**
     * Asignar técnico a solicitud
     */
    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        try {
            $technician = User::findOrFail($request->technician_id);
            $serviceRequest->assignTo($technician);

            return redirect()
                ->back()
                ->with('success', 'Técnico asignado exitosamente');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al asignar técnico: ' . $e->getMessage());
        }
    }

    /**
     * Marcar solicitud como completada
     */
    public function complete(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        try {
            $serviceRequest->markAsCompleted($request->resolution_notes);

            return redirect()
                ->back()
                ->with('success', 'Solicitud marcada como completada');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al completar solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar solicitud
     */
    public function cancel(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $serviceRequest->update([
                'status' => 'cancelled',
                'technical_notes' => $request->reason,
            ]);

            return redirect()
                ->back()
                ->with('success', 'Solicitud cancelada');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al cancelar solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Reintentar solicitud fallida
     */
    public function retry(ServiceRequest $serviceRequest)
    {
        try {
            if ($serviceRequest->status !== 'failed') {
                return redirect()
                    ->back()
                    ->with('error', 'Solo se pueden reintentar solicitudes fallidas');
            }

            // Resetear estado
            $serviceRequest->update([
                'status' => 'pending',
                'started_at' => null,
                'completed_at' => null,
            ]);

            // Si es automatizable, disparar job nuevamente
            if ($serviceRequest->canBeAutomated() && $serviceRequest->router) {
                \Modules\Network\Jobs\ProcessRouterReboot::dispatch($serviceRequest)
                    ->delay(now()->addSeconds(5));
            }

            return redirect()
                ->back()
                ->with('success', 'Solicitud reenviada a procesamiento');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al reintentar solicitud: ' . $e->getMessage());
        }
    }
}
