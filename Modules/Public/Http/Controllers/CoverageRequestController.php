<?php

namespace Modules\Public\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Public\Entities\CoverageRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

class CoverageRequestController extends Controller
{
    /**
     * Crear solicitud de cobertura
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'coordinates' => 'required|array',
            'coordinates.lat' => 'required|numeric|between:-90,90',
            'coordinates.lng' => 'required|numeric|between:-180,180',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos incompletos o inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verificar si ya existe una solicitud reciente con el mismo email
        $existingRequest = CoverageRequest::where('email', $request->email)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una solicitud activa. Te notificaremos cuando haya novedades.',
            ], 409);
        }

        // Contar solicitudes en la zona
        $requestsInZone = CoverageRequest::countInRadius(
            $request->coordinates['lat'],
            $request->coordinates['lng'],
            1 // 1 km de radio
        );

        // Crear la solicitud
        $coverageRequest = CoverageRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'latitude' => $request->coordinates['lat'],
            'longitude' => $request->coordinates['lng'],
            'comments' => $request->comments,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Incrementar contador
        $newCount = $requestsInZone + 1;

        // Si llegamos al umbral, notificar al equipo técnico
        if ($newCount >= 15) {
            $this->notifyTechnicalTeam($coverageRequest, $newCount);
        }

        // Enviar email de confirmación al solicitante
        $this->sendConfirmationEmail($coverageRequest);

        return response()->json([
            'success' => true,
            'message' => '¡Solicitud registrada exitosamente!',
            'data' => [
                'id' => $coverageRequest->id,
                'name' => $coverageRequest->name,
                'email' => $coverageRequest->email,
            ],
            'requests_in_zone' => $newCount,
            'threshold' => 15,
            'percentage' => min(100, ($newCount / 15) * 100),
        ], 201);
    }

    /**
     * Notificar al equipo técnico
     */
    protected function notifyTechnicalTeam($request, $count)
    {
        // Aquí puedes implementar la lógica de notificación
        // Por ejemplo, enviar email al equipo técnico, crear ticket, etc.

        \Log::info("⚠️ UMBRAL ALCANZADO: {$count} solicitudes en zona", [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        // TODO: Implementar notificación real
        // Notification::route('mail', config('mail.technical_team'))
        //     ->notify(new CoverageThresholdReached($request, $count));
    }

    /**
     * Enviar email de confirmación
     */
    protected function sendConfirmationEmail($request)
    {
        // TODO: Implementar email de confirmación
        \Log::info("📧 Email de confirmación enviado a: {$request->email}");
    }

    /**
     * Listar solicitudes (solo para admin)
     */
    public function index(Request $request): JsonResponse
    {
        $query = CoverageRequest::query();

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($requests);
    }

    /**
     * Ver detalle de solicitud
     */
    public function show($id): JsonResponse
    {
        $request = CoverageRequest::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $request,
        ]);
    }
}
