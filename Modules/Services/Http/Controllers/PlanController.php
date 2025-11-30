<?php

namespace Modules\Services\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Services\Services\PlanService;
use Modules\Services\Services\ServiceService;
use Modules\Services\Services\PromotionService;
use Modules\Services\Http\Requests\PlanRequest;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Repositories\PlanRepository;
use Modules\Services\Entities\Plan;

class PlanController extends Controller
{
    /**
     * @var PlanRepository
     */
    protected $planRepository;

    /**
     * @var PlanService
     */
    protected $planService;

    /**
     * @var ServiceService
     */
    protected $serviceService;

    /**
     * @var PromotionService
     */
    protected $promotionService;

    /**
     * PlanController constructor.
     *
     * @param PlanService $planService
     * @param ServiceService $serviceService
     * @param PromotionService $promotionService
     */
    public function __construct(
        PlanRepository $planRepository,
        PlanService $planService,
        ServiceService $serviceService,
        PromotionService $promotionService
    ) {
        $this->planRepository = $planRepository;
        $this->planService = $planService;
        $this->serviceService = $serviceService;
        $this->promotionService = $promotionService;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $serviceId = $request->input('service_id');

        if ($serviceId) {
            $result = $this->planService->getPlansByService($serviceId);
            $service = $result['service'] ?? null;
            $plans = $result['plans'] ?? [];
        } else {
            $result = $this->planService->getAllPlans();
            $service = null;
            $plans = $result['plans'];
        }

        return view('services::plans.index', [
            'plans' => $plans,
            'service' => $service
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request)
    {
        $serviceId = $request->input('service_id');

        $services = $this->serviceService->getAllServices(true)['services'];
        $promotions = $this->promotionService->getAllPromotions(true)['promotions'];

        \Log::info('Servicios obtenidos:', ['services' => $services]);

        $selectedService = null;
        if ($serviceId) {
            foreach ($services as $service) {
                if ($service->id == $serviceId) {
                    $selectedService = $service;
                    break;
                }
            }
        }

        return view('services::plans.create', [
            'services' => $services,
            'promotions' => $promotions,
            'selectedService' => $selectedService
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param PlanRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(PlanRequest $request)
    {
        $result = $this->planService->createPlan(
            $request->validated(),
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()->withErrors(['message' => $result['message']])->withInput();
        }

        if ($request->input('service_id')) {
            return redirect()->route('services.plans.index', ['service_id' => $request->input('service_id')])
                ->with('success', $result['message']);
        }

        return redirect()->route('services.plans.index')
            ->with('success', $result['message']);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $plan = $this->planRepository->find($id);
        $service = $plan->service;
        $activePromotions = $plan->promotions()->currentlyActive()->get();
        $inactivePromotions = $plan->promotions()->where('active', true)
            ->where(function($query) {
                $query->where('start_date', '>', now())
                    ->orWhere('end_date', '<', now());
            })->get();

        return view('services::plans.show', [
            'plan' => $plan,
            'service' => $service,
            'activePromotions' => $activePromotions,
            'inactivePromotions' => $inactivePromotions
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $plan = $this->planRepository->find($id);
        $services = $this->serviceService->getAllServices(true)['services'];
        $promotions = $this->promotionService->getAllPromotions()['promotions'];

        $selectedPromotions = $plan->promotions->pluck('id')->toArray();

        return view('services::plans.edit', [
            'plan' => $plan,
            'services' => $services,
            'promotions' => $promotions,
            'selectedPromotions' => $selectedPromotions
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param PlanRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PlanRequest $request, $id)
    {
        $result = $this->planService->updatePlan(
            $id,
            $request->validated(),
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()->withErrors(['message' => $result['message']])->withInput();
        }

        if ($request->input('service_id')) {
            return redirect()->route('services.plans.index', ['service_id' => $request->input('service_id')])
                ->with('success', $result['message']);
        }

        return redirect()->route('services.plans.index')
            ->with('success', $result['message']);
    }

    /**
     * Activate the specified resource in storage.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate($id, Request $request)
    {
        $result = $this->planService->togglePlanStatus(
            $id,
            true,
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()->withErrors(['message' => $result['message']]);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Deactivate the specified resource in storage.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deactivate($id, Request $request)
    {
        $result = $this->planService->togglePlanStatus(
            $id,
            false,
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()->withErrors(['message' => $result['message']]);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id, Request $request)
    {
        $plan = $this->planRepository->find($id);
        $serviceId = $plan->service_id;

        $result = $this->planService->deletePlan(
            $id,
            $request->ip()
        );

        if (!$result['success']) {
            return redirect()->back()->withErrors(['message' => $result['message']]);
        }

        if ($request->query('service_id')) {
            return redirect()->route('services.plans.index', ['service_id' => $serviceId])
                ->with('success', $result['message']);
        }

        return redirect()->route('services.plans.index')
            ->with('success', $result['message']);
    }

    public function apiIndex()
    {
        try {
            // Obtener solo planes activos con sus relaciones
            $plans = Plan::where('active', true)
                ->with(['service', 'promotions' => function ($query) {
                    $query->where('active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                }])
                //->orderBy('order', 'asc')
                ->orderBy('price', 'asc')
                ->get();

            // Formatear planes para el frontend
            $formattedPlans = $plans->map(function ($plan) {
                // Calcular precio con descuento
                $originalPrice = (float) $plan->price;
                $discountedPrice = $originalPrice;
                $activePromotion = null;
                $discountPercentage = 0;

                // Si tiene promociones activas, calcular descuento
                if ($plan->promotions && $plan->promotions->count() > 0) {
                    $promotion = $plan->promotions->first();
                    $activePromotion = [
                        'id' => $promotion->id,
                        'name' => $promotion->name,
                        'description' => $promotion->description ?? '',
                    ];

                    if ($promotion->discount_type === 'percentage') {
                        $discountPercentage = (float) $promotion->discount;
                        $discountedPrice = $originalPrice * (1 - ($discountPercentage / 100));
                    } else {
                        $discountedPrice = max(0, $originalPrice - (float) $promotion->discount);
                        $discountPercentage = (($originalPrice - $discountedPrice) / $originalPrice) * 100;
                    }
                }

                // Parsear características
                $features = [];
                if ($plan->features) {
                    $features = is_string($plan->features) ? json_decode($plan->features, true) : $plan->features;
                    $features = is_array($features) ? $features : [];
                }

                // Determinar rango de velocidad
                $speed = (float) ($plan->download_speed ?? 0);
                $speedRange = '0-50';
                if ($speed >= 50 && $speed < 100) $speedRange = '50-100';
                elseif ($speed >= 100 && $speed < 200) $speedRange = '100-200';
                elseif ($speed >= 200 && $speed < 500) $speedRange = '200-500';
                elseif ($speed >= 500) $speedRange = '500+';

                // Determinar rango de precio
                $paymentRange = '0-50';
                if ($discountedPrice >= 50 && $discountedPrice < 100) $paymentRange = '50-100';
                elseif ($discountedPrice >= 100 && $discountedPrice < 150) $paymentRange = '100-150';
                elseif ($discountedPrice >= 150 && $discountedPrice < 200) $paymentRange = '150-200';
                elseif ($discountedPrice >= 200) $paymentRange = '200+';

                return [
                    'id' => $plan->id,
                    'type' => $discountedPrice < $originalPrice ? 'serviceTwo' : 'service',
                    'title' => $plan->name,
                    'description' => $plan->description ?? '',
                    'price' => round($discountedPrice, 2),
                    'originalPrice' => round($originalPrice, 2),
                    'discountedPrice' => round($discountedPrice, 2),
                    'currency' => 'S/',
                    'period' => 'mensual',
                    'hasDiscount' => $discountedPrice < $originalPrice,
                    'discountPercentage' => round($discountPercentage, 0),
                    'speed' => $speed,
                    'speedUnit' => 'Mbps',
                    'downloadSpeed' => (float) ($plan->download_speed ?? 0),
                    'uploadSpeed' => (float) ($plan->upload_speed ?? 0),
                    'speedRange' => $speedRange,
                    'paymentRange' => $paymentRange,
                    'features' => $features,
                    'service' => [
                        'id' => $plan->service->id ?? null,
                        'name' => $plan->service->name ?? 'Servicio',
                        'type' => $plan->service->service_type ?? 'internet',
                    ],
                    'promotion' => $activePromotion,
                    'buttonText' => 'Contratar',
                    'technicalTitle' => 'Especificaciones Técnicas',
                    'technicalDetails' => [
                        'Descarga ' . $plan->download_speed . ' Mbps',
                        'Subida ' . $plan->upload_speed . ' Mbps'
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlans,
                'total' => $formattedPlans->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en apiIndex de PlanController: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener planes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Obtener un plan específico por ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiShow($id)
    {
        try {
            $plan = $this->planRepository->query()
                ->where('id', $id)
                ->where('active', true)
                ->with(['service', 'promotions' => function ($query) {
                    $query->where('active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                }])
                ->firstOrFail();

            $formattedPlan = $this->formatPlanForFrontend($plan);

            return response()->json([
                'success' => true,
                'data' => $formattedPlan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plan no encontrado',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * API: Obtener planes por servicio
     *
     * @param int $serviceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiByService($serviceId)
    {
        try {
            $plans = $this->planRepository->query()
                ->where('service_id', $serviceId)
                ->where('active', true)
                ->with(['service', 'promotions' => function ($query) {
                    $query->where('active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                }])
                //->orderBy('order', 'asc')
                ->orderBy('price', 'asc')
                ->get();

            $formattedPlans = $plans->map(function ($plan) {
                return $this->formatPlanForFrontend($plan);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlans,
                'total' => $formattedPlans->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener planes por servicio',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatear plan para el frontend Vue.js
     *
     * @param $plan
     * @return array
     */
    private function formatPlanForFrontend($plan): array
    {
        // Calcular precio con descuento si hay promociones activas
        $originalPrice = (float) $plan->price;
        $discountedPrice = $originalPrice;
        $activePromotion = null;
        $discountPercentage = 0;

        if ($plan->promotions && $plan->promotions->count() > 0) {
            $promotion = $plan->promotions->first();
            $activePromotion = [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'description' => $promotion->description,
                'discount_type' => $promotion->discount_type,
                'discount_value' => (float) $promotion->discount_value,
                'start_date' => $promotion->start_date,
                'end_date' => $promotion->end_date,
            ];

            if ($promotion->discount_type === 'percentage') {
                $discountPercentage = (float) $promotion->discount_value;
                $discountedPrice = $originalPrice * (1 - ($discountPercentage / 100));
            } else {
                $discountedPrice = max(0, $originalPrice - (float) $promotion->discount_value);
                $discountPercentage = (($originalPrice - $discountedPrice) / $originalPrice) * 100;
            }
        }

        // Parsear características del plan
        $features = json_decode($plan->features, true) ?? [];
        $technicalDetails = json_decode($plan->technical_specifications, true) ?? [];

        // Determinar el rango de velocidad para filtros
        $speed = (float) ($plan->download_speed ?? 0);
        $speedRange = $this->getSpeedRange($speed);

        // Determinar el rango de pago para filtros
        $paymentRange = $this->getPaymentRange($discountedPrice);

        // Formato compatible con CardService y CardServiceTwo de Vue.js
        return [
            'id' => $plan->id,
            'type' => $this->determineCardType($plan),
            'title' => $plan->name,
            'description' => $plan->description,

            // Precios
            'price' => round($discountedPrice, 2),
            'originalPrice' => round($originalPrice, 2),
            'discountedPrice' => round($discountedPrice, 2),
            'currency' => 'S/',
            'period' => $plan->billing_cycle ?? 'mensual',
            'hasDiscount' => $discountedPrice < $originalPrice,
            'discountPercentage' => round($discountPercentage, 0),

            // Velocidades
            'speed' => $speed,
            'speedUnit' => 'Mbps',
            'downloadSpeed' => (float) ($plan->download_speed ?? 0),
            'uploadSpeed' => (float) ($plan->upload_speed ?? 0),
            'speedDescription' => $this->getSpeedDescription($speed),
            'speedRange' => $speedRange,

            // Rangos para filtros
            'paymentRange' => $paymentRange,

            // Características
            'features' => $features,
            'technicalDetails' => $technicalDetails,
            'benefits' => $this->extractBenefits($plan),

            // Información del servicio
            'service' => [
                'id' => $plan->service->id ?? null,
                'name' => $plan->service->name ?? 'Servicio',
                'type' => $plan->service->type ?? 'internet',
                'description' => $plan->service->description ?? '',
            ],

            // Promoción activa
            'promotion' => $activePromotion,

            // Metadatos
            'isPopular' => $plan->is_featured ?? false,
            'popularity' => $plan->popularity_score ?? 0,
            'buttonText' => 'Contratar',
            'technicalTitle' => 'Especificaciones Técnicas',
            //'order' => $plan->order ?? 999,

            // Información adicional
            'contract_duration' => $plan->contract_duration ?? 12,
            'installation_cost' => (float) ($plan->installation_cost ?? 0),
            'equipment_cost' => (float) ($plan->equipment_cost ?? 0),
        ];
    }

    /**
     * Determinar el tipo de card para el frontend
     */
    private function determineCardType($plan): string
    {
        // Si tiene promoción activa, usar CardServiceTwo (más destacado)
        if ($plan->promotions && $plan->promotions->count() > 0) {
            return 'serviceTwo';
        }

        // Si es un plan destacado
        if ($plan->is_featured) {
            return 'serviceTwo';
        }

        // Por defecto usar CardService
        return 'service';
    }

    /**
     * Obtener descripción de velocidad
     */
    private function getSpeedDescription($speed): string
    {
        if ($speed < 50) {
            return 'Navegación básica';
        } elseif ($speed < 100) {
            return 'Ideal para streaming';
        } elseif ($speed < 300) {
            return 'Para toda la familia';
        } elseif ($speed < 600) {
            return 'Ultra rápido';
        } else {
            return 'Velocidad extrema';
        }
    }

    /**
     * Obtener rango de velocidad para filtros
     */
    private function getSpeedRange($speed): string
    {
        if ($speed < 50) {
            return '0-50';
        } elseif ($speed < 100) {
            return '50-100';
        } elseif ($speed < 200) {
            return '100-200';
        } elseif ($speed < 500) {
            return '200-500';
        } else {
            return '500+';
        }
    }

    /**
     * Obtener rango de pago para filtros
     */
    private function getPaymentRange($price): string
    {
        if ($price < 50) {
            return '0-50';
        } elseif ($price < 100) {
            return '50-100';
        } elseif ($price < 150) {
            return '100-150';
        } elseif ($price < 200) {
            return '150-200';
        } else {
            return '200+';
        }
    }

    /**
     * Extraer beneficios del plan
     */
    private function extractBenefits($plan): array
    {
        $benefits = [];

        // Beneficios basados en velocidad
        if ($plan->download_speed >= 100) {
            $benefits[] = 'Streaming 4K sin interrupciones';
        }

        if ($plan->download_speed >= 300) {
            $benefits[] = 'Gaming online de alta velocidad';
        }

        // Beneficios adicionales
        if ($plan->installation_cost == 0) {
            $benefits[] = 'Instalación gratuita';
        }

        if ($plan->equipment_cost == 0) {
            $benefits[] = 'Router WiFi incluido';
        }

        // Agregar características personalizadas del plan
        $features = json_decode($plan->features, true) ?? [];
        foreach ($features as $feature) {
            if (is_string($feature)) {
                $benefits[] = $feature;
            } elseif (is_array($feature) && isset($feature['name'])) {
                $benefits[] = $feature['name'];
            }
        }

        return $benefits;
    }
}
