<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Network\Services\RouterService;
use Modules\Network\Entities\Router;
use Modules\Billing\Entities\Payment;
use Illuminate\Support\Facades\Storage;

class CustomerAPIController extends Controller
{
    protected $routerService;

    public function __construct(RouterService $routerService)
    {
        $this->routerService = $routerService;
    }

    /**
     * Dashboard del cliente
     */
    public function dashboard(Request $request)
    {
        $customer = $request->user()->customer;

        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Obtener contrato activo
        $contract = $customer->contracts()
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$contract) {
            return response()->json([
                'customer' => [
                    'name' => $customer->user->name,
                    'email' => $customer->user->email,
                ],
                'service' => null,
                'message' => 'No tiene servicio activo'
            ]);
        }

        return response()->json([
            'customer' => [
                'name' => $customer->user->name,
                'email' => $customer->user->email,
            ],
            'service' => [
                'plan_name' => $contract->plan->name,
                'download_speed' => $contract->plan->download_speed,
                'upload_speed' => $contract->plan->upload_speed,
                'price' => $contract->plan->price,
                'status' => $contract->status,
                'next_payment' => $contract->next_billing_date,
            ]
        ]);
    }

    /**
     * Reiniciar línea del cliente
     */
    public function restartLine(Request $request)
    {
        $customer = $request->user()->customer;

        // Buscar router asignado al cliente
        $routerCustomer = $customer->routers()->first();

        if (!$routerCustomer) {
            return response()->json(['error' => 'No tiene router asignado'], 404);
        }

        $router = $routerCustomer->pivot->router;
        $username = $routerCustomer->pivot->pppoe_username;

        try {
            // Desconectar y reconectar
            $this->routerService->suspendClient($router, $username);
            sleep(2);
            $this->routerService->activateClient($router, $username);

            return response()->json([
                'success' => true,
                'message' => 'Línea reiniciada correctamente. Espere 1-2 minutos.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar línea'
            ], 500);
        }
    }

    /**
     * Subir comprobante de pago
     */
    public function uploadPayment(Request $request)
    {
        $request->validate([
            'receipt' => 'required|image|max:5120', // 5MB max
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:yape,plin,transferencia,efectivo'
        ]);

        $customer = $request->user()->customer;

        // Guardar imagen
        $path = $request->file('receipt')->store('payment-receipts', 'public');

        // Crear registro de pago pendiente
        Payment::create([
            'customer_id' => $customer->id,
            'amount' => $request->amount,
            'payment_method' => $request->method,
            'status' => 'pending', // Admin debe aprobar
            'receipt_path' => $path,
            'payment_date' => now(),
            'notes' => 'Comprobante subido por el cliente',
        ]);

        // Notificar a admin (opcional)
        // ...

        return response()->json([
            'success' => true,
            'message' => 'Comprobante recibido. Verificaremos tu pago pronto.'
        ]);
    }
}
