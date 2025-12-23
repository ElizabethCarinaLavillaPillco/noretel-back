<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Services\CustomerService;
use Modules\Customer\Http\Requests\StoreCustomerRequest;
use Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Modules\Customer\Entities\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Network\Services\RouterService;
use Modules\Network\Entities\Router;
use Modules\Billing\Entities\Payment;

class CustomerController extends Controller
{
    /**
     * @var CustomerService
     */
    protected $customerService;

    /**
     * CustomerController constructor.
     *
     * @param CustomerService $customerService
     */
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'type', 'segment', 'active', 'date_from', 'date_to'
        ]);
        
        $perPage = $request->get('per_page', 15);
        
        $customers = $this->customerService->searchCustomers($filters, $perPage);
        
        return view('customer::customers.index', compact('customers', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $customerTypes = [
            'individual' => 'Individual',
            'business' => 'Empresa'
        ];
        
        $segments = [
            'residential' => 'Residencial',
            'business' => 'Empresarial',
            'corporate' => 'Corporativo',
            'public' => 'Sector Público'
        ];
        
        return view('customer::customers.create', compact('customerTypes', 'segments'));
    }

    /**
     * Store a newly created resource in storage.
     * @param StoreCustomerRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerRequest $request)
    {
        $customerData = $request->except(['addresses', 'emergency_contacts']);
        $addressesData = $request->input('addresses', []);
        
        $result = $this->customerService->createCustomer(
            $customerData,
            $addressesData,
            Auth::id(),
            $request->ip()
        );
        
        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }
        
        return redirect()->route('customer.customers.show', $result['customer']->id)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $customer = Customer::with(['addresses', 'emergencyContacts', 'documents', 'leads'])
            ->findOrFail($id);
        
        return view('customer::customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $customer = Customer::with(['addresses', 'emergencyContacts'])
            ->findOrFail($id);
        
        $customerTypes = [
            'individual' => 'Individual',
            'business' => 'Empresa'
        ];
        
        $segments = [
            'residential' => 'Residencial',
            'business' => 'Empresarial',
            'corporate' => 'Corporativo',
            'public' => 'Sector Público'
        ];
        
        return view('customer::customers.edit', compact('customer', 'customerTypes', 'segments'));
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateCustomerRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        $customerData = $request->except(['addresses', 'emergency_contacts']);
        $addressesData = $request->input('addresses', []);
        
        $result = $this->customerService->updateCustomer(
            $id,
            $customerData,
            $addressesData,
            Auth::id(),
            $request->ip()
        );
        
        if (!$result['success']) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result['message']);
        }
        
        return redirect()->route('customer.customers.show', $id)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $result = $this->customerService->deleteCustomer(
            $id,
            Auth::id(),
            $request->ip()
        );
        
        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }
        
        return redirect()->route('customer.customers.index')
            ->with('success', $result['message']);
    }

    /**
     * Activate a customer.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function activate($id, Request $request)
    {
        $result = $this->customerService->changeStatus(
            $id,
            true,
            Auth::id(),
            $request->ip()
        );
        
        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }
        
        return redirect()->back()
            ->with('success', $result['message']);
    }

    /**
     * Deactivate a customer.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function deactivate($id, Request $request)
    {
        $result = $this->customerService->changeStatus(
            $id,
            false,
            Auth::id(),
            $request->ip()
        );
        
        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['message']);
        }
        
        return redirect()->back()
            ->with('success', $result['message']);
    }

    // ========================================================================
    // MÉTODOS API PARA CLIENTES (Frontend Vue)
    // ========================================================================

    /**
     * API: Dashboard del cliente
     */
    public function apiDashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();
        
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
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'service' => null,
                'message' => 'No tiene servicio activo'
            ]);
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'name' => $user->name,
                'email' => $user->email,
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
     * API: Reiniciar línea del cliente
     */
    public function apiRestartLine(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();
        
        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Buscar router asignado al cliente
        $routerCustomer = \DB::table('router_customer')
            ->where('customer_id', $customer->id)
            ->where('connection_status', 'active')
            ->first();
        
        if (!$routerCustomer) {
            return response()->json(['error' => 'No tiene router asignado'], 404);
        }

        $router = Router::find($routerCustomer->router_id);
        $username = $routerCustomer->pppoe_username;

        try {
            $routerService = app(RouterService::class);
            
            // Desconectar y reconectar
            $routerService->suspendClient($router, $username);
            sleep(2);
            $routerService->activateClient($router, $username);

            return response()->json([
                'success' => true,
                'message' => 'Línea reiniciada correctamente. Espere 1-2 minutos.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar línea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Subir comprobante de pago
     */
    public function apiUploadPayment(Request $request)
    {
        $request->validate([
            'receipt' => 'required|image|max:5120', // 5MB max
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:yape,plin,transferencia,efectivo'
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $customer = Customer::where('user_id', $user->id)->first();
        
        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        try {
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

            // TODO: Notificar a admin por email/SMS
            // event(new PaymentReceiptUploaded($customer, $payment));

            return response()->json([
                'success' => true,
                'message' => 'Comprobante recibido. Verificaremos tu pago pronto.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir comprobante: ' . $e->getMessage()
            ], 500);
        }
    }
}