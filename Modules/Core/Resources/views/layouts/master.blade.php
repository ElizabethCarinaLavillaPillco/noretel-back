<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - NorETEL CRM</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Estilos personalizados -->
    <link href="{{ asset('modules/core/css/style.css') }}" rel="stylesheet">
    <!-- Estilos del módulo Customer cuando sea necesario -->
    @if(request()->segment(1) == 'customer')
    <link href="{{ asset('modules/customer/css/style.css') }}" rel="stylesheet">
    @endif

    <style>
        :root {
            --primary-color: #2c5282;
            --primary-hover: #1a365d;
            --secondary-color: #4299e1;
            --accent-color: #f6ad55;
            --success-color: #38a169;
            --danger-color: #e53e3e;
            --warning-color: #d69e2e;
            --info-color: #3182ce;
            --dark-color: #1a202c;
            --light-color: #f8f9fa;
            --text-color: #2d3748;
            --text-muted: #718096;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: var(--text-color);
        }

        /* Sidebar styles */
        #sidebar {
            min-height: 100vh;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 100;
            background-color: var(--dark-color);
            transition: all 0.3s ease;
            width: 260px;
        }

        #sidebar .sidebar-brand {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #sidebar .sidebar-brand-text {
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        #sidebar .logo-initial {
            background-color: var(--accent-color);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }

        #sidebar .nav-item {
            margin: 4px 12px;
        }

        #sidebar .nav-link {
            padding: 0.8rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        #sidebar .sidebar-heading {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            padding: 1rem 1.5rem 0.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        #sidebar .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 1rem 1rem;
        }

        #sidebar .nav-item .nav-link i {
            width: 1.25rem;
            margin-right: 0.75rem;
            text-align: center;
            font-size: 0.9rem;
        }

        #sidebar .sidebar-footer {
            padding: 1rem;
            font-size: 0.8rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        /* Module groups */
        .sidebar-module-group {
            margin-bottom: 0.75rem;
        }

        .sidebar-module-title {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            margin: 0.75rem 1.5rem 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
        }

        .sidebar-module-title i {
            margin-right: 0.5rem;
            font-size: 0.8rem;
        }

        /* Main content area */
        .content-wrapper {
            flex: 1;
            transition: all 0.3s ease;
        }

        /* Navbar styles */
        .topbar {
            height: 70px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            background-color: white;
            z-index: 99;
        }

        .topbar .navbar-nav .nav-item {
            position: relative;
            margin: 0 5px;
        }

        .topbar .navbar-nav .nav-link {
            color: var(--text-muted);
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .topbar .navbar-nav .nav-link:hover {
            background-color: var(--light-color);
            color: var(--primary-color);
        }

        .topbar .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .topbar-divider {
            width: 0;
            border-right: 1px solid var(--border-color);
            height: 2rem;
            margin: auto 0.5rem;
        }

        /* Card styles */
        .card {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: none;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        /* Action buttons */
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-color);
            color: var(--text-muted);
            transition: all 0.2s ease;
            border: none;
        }

        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .action-btn.primary {
            background-color: var(--primary-color);
            color: white;
        }

        .action-btn.primary:hover {
            background-color: var(--primary-hover);
        }

        .action-btn-group {
            display: flex;
            gap: 0.5rem;
        }

        /* Notifications counter and dropdown */
        .notification-counter {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            text-align: center;
            line-height: 18px;
        }

        .notification-item {
            display: flex;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }

        .notification-item:hover {
            background-color: var(--light-color);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .notification-icon.primary {
            background-color: rgba(66, 153, 225, 0.1);
            color: var(--primary-color);
        }

        .notification-content {
            flex: 1;
        }

        .notification-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* User dropdown */
        .user-dropdown {
            display: flex;
            align-items: center;
        }

        .user-dropdown .img-profile {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border: 2px solid var(--border-color);
        }

        .user-dropdown .user-name {
            font-size: 0.9rem;
            font-weight: 500;
            margin-right: 0.75rem;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert i {
            margin-right: 0.5rem;
        }

        /* Collapsible sidebar for mobile */
        @media (max-width: 767.98px) {
            #sidebar {
                margin-left: -260px;
                position: fixed;
                height: 100%;
            }

            #sidebar.active {
                margin-left: 0;
            }

            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="d-flex">
        @auth
            <!-- Sidebar izquierdo cuando está autenticado -->
            <nav id="sidebar">
                <div class="sidebar-brand">
                    <div class="d-flex align-items-center">
                        <div class="logo-initial">N</div>
                        <div class="sidebar-brand-text">NorETEL CRM</div>
                    </div>
                    <button type="button" id="sidebarCollapseBtn" class="btn btn-sm btn-dark d-md-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="sidebar-divider"></div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('core.dashboard') ? 'active' : '' }}"
                           href="{{ route('core.dashboard') }}">
                           <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>


                <!-- Módulo Servicios -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-plug"></i> Servicios
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('services'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('services.services.*') ? 'active' : '' }}"
                               href="{{ route('services.services.index') }}">
                               <i class="fas fa-cogs"></i> Gestión de Servicios
                            </a>
                        </li>
                        @endif
                        @if (auth()->user()->canViewModule('plans'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('services.plans.*') ? 'active' : '' }}"
                               href="{{ route('services.plans.index') }}">
                               <i class="fas fa-network-wired"></i> Planes
                            </a>
                        </li>
                        @endif
                        @if (auth()->user()->canViewModule('promotions'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('services.promotions.*') ? 'active' : '' }}"
                               href="{{ route('services.promotions.index') }}">
                               <i class="fas fa-tags"></i> Promociones
                            </a>
                        </li>
                        @endif
                        @if (auth()->user()->canViewModule('additional-services'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('services.additional-services.*') ? 'active' : '' }}"
                               href="{{ route('services.additional-services.index') }}">
                               <i class="fas fa-plus-circle"></i> Servicios Adicionales
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Módulo Clientes -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-users"></i> Clientes
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('customers'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}"
                               href="{{ route('customer.dashboard') }}">
                               <i class="fas fa-chart-line"></i> Dashboard Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.customers.*') ? 'active' : '' }}"
                               href="{{ route('customer.customers.index') }}">
                               <i class="fas fa-user-friends"></i> Gestión de Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.documents.*') ? 'active' : '' }}"
                               href="{{ route('customer.documents.index') }}">
                               <i class="fas fa-file-alt"></i> Documentos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.interactions.*') ? 'active' : '' }}"
                               href="{{ route('customer.interactions.index') }}">
                               <i class="fas fa-comments"></i> Interacciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.leads.*') ? 'active' : '' }}"
                               href="{{ route('customer.leads.index') }}">
                               <i class="fas fa-user-tag"></i> Leads
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Módulo Contratos -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-file-signature"></i> Contratos
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('contracts'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contract.dashboard') ? 'active' : '' }}"
                            href="{{ route('contract.dashboard') }}">
                            <i class="fas fa-chart-line"></i> Dashboard Contratos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contract.contracts.*') ? 'active' : '' }}"
                            href="{{ route('contract.contracts.index') }}">
                            <i class="fas fa-file-contract"></i> Gestión de Contratos
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('installations'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contract.installations.*') ? 'active' : '' }}"
                            href="{{ route('contract.installations.index') }}">
                            <i class="fas fa-tools"></i> Instalaciones
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('routes'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contract.routes.*') ? 'active' : '' }}"
                            href="{{ route('contract.routes.index') }}">
                            <i class="fas fa-map-marked-alt"></i> Rutas
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('slas'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('contract.slas.*') ? 'active' : '' }}"
                            href="{{ route('contract.slas.index') }}">
                            <i class="fas fa-handshake"></i> SLAs
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Módulo Infraestructura de Red -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-server"></i> Infraestructura
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('network'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('network.dashboard') ? 'active' : '' }}"
                            href="{{ route('network.dashboard') }}">
                            <i class="fas fa-chart-network"></i> Dashboard Red
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('routers'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('network.routers.*') ? 'active' : '' }}"
                            href="{{ route('network.routers.index') }}">
                            <i class="fas fa-router"></i> Routers
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('nodes'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('network.nodes.*') ? 'active' : '' }}"
                            href="{{ route('network.nodes.index') }}">
                            <i class="fas fa-network-wired"></i> Nodos
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('network_automation'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('network.automation.*') ? 'active' : '' }}"
                            href="{{ route('network.automation.index') }}">
                            <i class="fas fa-magic"></i> Automatizaciones
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('service_requests'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('network.service-requests.*') ? 'active' : '' }}"
                            href="{{ route('network.service-requests.index') }}">
                            <i class="fas fa-ticket-alt"></i> Solicitudes de Servicio
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Módulo Facturación y Cobranza -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-file-invoice-dollar"></i> Facturación
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('invoices') || auth()->user()->canViewModule('payments'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('billing.dashboard') ? 'active' : '' }}"
                            href="{{ route('billing.dashboard') }}">
                            <i class="fas fa-chart-line"></i> Dashboard Facturación
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('invoices'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('billing.invoices.*') ? 'active' : '' }}"
                            href="{{ route('billing.invoices.index') }}">
                            <i class="fas fa-file-invoice"></i> Facturas
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('payments'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('billing.payments.*') ? 'active' : '' }}"
                            href="{{ route('billing.payments.index') }}">
                            <i class="fas fa-money-bill-wave"></i> Pagos
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('credit_notes'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('billing.credit-notes.*') ? 'active' : '' }}"
                            href="{{ route('billing.credit-notes.index') }}">
                            <i class="fas fa-receipt"></i> Notas de Crédito
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('financial_reports'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('billing.reports') ? 'active' : '' }}"
                            href="{{ route('billing.reports') }}">
                            <i class="fas fa-chart-bar"></i> Reportes Financieros
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                <!-- Módulo Sistema -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-cogs"></i> Sistema
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('users'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.users.*') ? 'active' : '' }}"
                               href="{{ route('core.users.index') }}">
                               <i class="fas fa-users"></i> Usuarios
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('roles'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.roles.*') ? 'active' : '' }}"
                               href="{{ route('core.roles.index') }}">
                               <i class="fas fa-user-shield"></i> Roles y Permisos
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('configuration'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.config.*') ? 'active' : '' }}"
                               href="{{ route('core.config.index') }}">
                               <i class="fas fa-cog"></i> Configuraciones
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('security'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.security.*') ? 'active' : '' }}"
                               href="{{ route('core.security.index') }}">
                               <i class="fas fa-shield-alt"></i> Seguridad
                            </a>
                        </li>
                        @endif

                        @if(auth()->user()->canViewModule('audit'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.audit.*') ? 'active' : '' }}"
                               href="{{ route('core.audit.index') }}">
                               <i class="fas fa-clipboard-list"></i> Auditoría
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <!-- Módulo Workflow -->
                <div class="sidebar-module-group">
                    <div class="sidebar-module-title">
                        <i class="fas fa-project-diagram"></i> Procesos
                    </div>
                    <ul class="nav flex-column">
                        @if(auth()->user()->canViewModule('workflows'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.workflows.*') ? 'active' : '' }}"
                               href="{{ route('core.workflows.index') }}">
                               <i class="fas fa-sitemap"></i> Workflows
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('core.notifications.*') ? 'active' : '' }}"
                               href="{{ route('core.notifications.index') }}">
                               <i class="fas fa-bell"></i> Notificaciones
                               @if(isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                                <span class="notification-counter">{{ $unreadNotificationsCount }}</span>
                               @endif
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-divider"></div>

                <div class="sidebar-footer">
                    <p>© {{ date('Y') }} NorETEL CRM v1.0</p>
                </div>
            </nav>
        @endauth

        <!-- Contenido principal -->
        <div class="content-wrapper">
            @auth
            <!-- Navbar superior -->
            <nav class="navbar navbar-expand topbar mb-4 static-top">
                <div class="container-fluid px-4">
                    <button type="button" id="sidebarCollapse" class="action-btn d-md-none">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="d-flex align-items-center">
                        <h1 class="h4 mb-0 text-gray-800">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <ul class="navbar-nav ml-auto">
                        <!-- Dropdown de notificaciones -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                @if(isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                                <span class="notification-counter">{{ $unreadNotificationsCount }}</span>
                                @endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="alertsDropdown" style="min-width: 320px;">
                                <div class="dropdown-header bg-primary text-white py-2 px-3">
                                    <i class="fas fa-bell me-2"></i> Centro de Notificaciones
                                </div>

                                @if(isset($recentNotifications) && count($recentNotifications) > 0)
                                    @foreach($recentNotifications as $notification)
                                    <a class="notification-item" href="{{ route('core.notifications.index') }}">
                                        <div class="notification-icon primary">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-time">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
                                            <div class="{{ !isset($notification->metadata['read']) || !$notification->metadata['read'] ? 'fw-bold' : '' }}">
                                                {{ Str::limit($notification->action_detail, 50) }}
                                            </div>
                                        </div>
                                    </a>
                                    @endforeach
                                @else
                                    <div class="notification-item justify-content-center">
                                        No hay notificaciones
                                    </div>
                                @endif

                                <div class="dropdown-footer bg-light text-center py-2">
                                    <a href="{{ route('core.notifications.index') }}" class="text-decoration-none">Ver todas las notificaciones</a>
                                </div>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Dropdown de usuario -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-dropdown">
                                    <span class="user-name d-none d-lg-block">{{ auth()->user()->username }}</span>
                                    <img class="img-profile rounded-circle"
                                        src="https://ui-avatars.com/api/?name={{ auth()->user()->username }}&background=random&color=fff">
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                                <div class="dropdown-header py-2">
                                    <small class="text-muted">Cuenta de usuario</small>
                                </div>
                                <a class="dropdown-item" href="{{ route('core.change-password') }}">
                                    <i class="fas fa-key fa-sm fa-fw me-2 text-gray-400"></i>
                                    Cambiar contraseña
                                </a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('core.auth.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            @endauth

            <!-- Contenedor principal -->
            <div class="container-fluid px-4">
                <!-- Mensajes de alerta -->
                <div class="alerts-container mb-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show">
                            <i class="fas fa-info-circle"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> Hay errores en el formulario:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>

                <!-- Contenido de la página -->
                <div class="content-container mb-4">
                    @yield('content')
                </div>

                <!-- Footer -->
                <footer class="py-3 mt-auto">
                    <div class="container">
                        <div class="text-center text-muted">
                            <span>© {{ date('Y') }} NorETEL - Todos los derechos reservados</span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

    <!-- Script para Chart.js - necesario para dashboard de clientes -->
    @if(request()->segment(1) == 'users' && request()->segment(2) == '')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif

    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
            const sidebar = document.getElementById('sidebar');

            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function() {
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }

            if (sidebarCollapseBtn) {
                sidebarCollapseBtn.addEventListener('click', function() {
                    if (sidebar) {
                        sidebar.classList.toggle('active');
                    }
                });
            }

            // Cerrar alertas automáticamente después de 5 segundos
            const alerts = document.querySelectorAll('.alert:not(.alert-danger)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
        });

        // Componente para agrupar botones de acción (para usar en tablas, formularios, etc.)
        class ActionButtons {
            constructor(container, buttons) {
                this.container = container;
                this.buttons = buttons;
                this.render();
            }

            render() {
                const buttonGroup = document.createElement('div');
                buttonGroup.className = 'action-btn-group';

                this.buttons.forEach(button => {
                    const btn = document.createElement('button');
                    btn.type = button.type || 'button';
                    btn.className = `action-btn ${button.class || ''}`;
                    btn.title = button.title || '';

                    if (button.id) btn.id = button.id;
                    if (button.onclick) btn.onclick = button.onclick;

                    const icon = document.createElement('i');
                    icon.className = button.icon;
                    btn.appendChild(icon);

                    buttonGroup.appendChild(btn);
                });

                this.container.appendChild(buttonGroup);
                return buttonGroup;
            }
        }

        // Helper para crear botones de acción más fácilmente
        function createActionButtons(containerId, buttons) {
            const container = document.getElementById(containerId);
            if (container) {
                return new ActionButtons(container, buttons);
            }
            return null;
        }
    </script>

    @stack('scripts')
</body>
</html>
