<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Banking Admin'))</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.min.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @php
        use Illuminate\Support\Str;
    @endphp
    
    <!-- Styles -->
    @yield('css')
    <style>
        :root {
            /* Color Palettes */
            --primary-blue: #3049a7;
            --primary-purple: #7c3aed;
            --primary-green: #059669;
            --primary-orange: #ea580c;
            --primary-pink: #db2777;
            --primary-teal: #0891b2;
            
            /* Current Theme Colors */
            --primary: var(--primary-blue);
            --primary-light: #4a6bc8;
            --primary-dark: #1e3a8a;
            
            /* Neutral Colors */
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Semantic Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            /* Layout - Ajustado para mejor alineación */
            --sidebar-width: 260px;
            --sidebar-collapsed: 65px;
            --header-height: 70px;
            
            /* Spacing - Optimizado */
            --space-xs: 4px;
            --space-sm: 8px;
            --space-md: 12px;
            --space-lg: 16px;
            --space-xl: 24px;
            --space-2xl: 32px;
            --space-3xl: 48px;
            
            /* Border Radius */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            
            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            /* Transitions */
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Light Theme */
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: var(--gray-50);
            --bg-tertiary: var(--gray-100);
            --text-primary: var(--gray-900);
            --text-secondary: var(--gray-600);
            --text-tertiary: var(--gray-400);
            --border-primary: var(--gray-200);
            --border-secondary: var(--gray-100);
        }
        
        /* Dark Theme */
        [data-theme="dark"] {
            --bg-primary: var(--gray-900);
            --bg-secondary: var(--gray-800);
            --bg-tertiary: var(--gray-700);
            --text-primary: var(--gray-50);
            --text-secondary: var(--gray-300);
            --text-tertiary: var(--gray-500);
            --border-primary: var(--gray-700);
            --border-secondary: var(--gray-600);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            font-weight: 400;
            letter-spacing: -0.01em;
            
            overflow-x: hidden;
        }

        /* Scrollbar mejorado */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }

        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: var(--gray-600);
        }

        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        /* Header mejorado */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            align-items: center;
            padding: 0 var(--space-lg);
            z-index: 1000;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: var(--space-xl);
        }

        .sidebar-toggle {
            width: 44px;
            height: 44px;
            border: none;
            background: transparent;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            
        }

        .sidebar-toggle:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            transform: scale(1.05);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            text-decoration: none;
        }

        .brand-logo-img {
            width: auto;
            height: 36px;
            object-fit: contain;
            border-radius: var(--radius-sm);
        }

        .brand-logo-fallback {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            box-shadow: var(--shadow-md);
        }

        .brand-text {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        /* Theme Toggle mejorado */
        .theme-toggle {
            position: relative;
            width: 52px;
            height: 26px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: 13px;
            cursor: pointer;
            
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: var(--bg-primary);
            border-radius: 50%;
            
            box-shadow: var(--shadow-sm);
        }

        .theme-toggle.dark {
            background: var(--primary);
            border-color: var(--primary);
        }

        .theme-toggle.dark::before {
            transform: translateX(26px);
            background: white;
        }

        /* Color Palette mejorada */
        .color-palette {
            display: flex;
            gap: var(--space-sm);
            padding: var(--space-sm);
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .color-option {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            cursor: pointer;
            
            border: 2px solid transparent;
            position: relative;
        }

        .color-option:hover {
            transform: scale(1.15);
            box-shadow: var(--shadow-md);
        }

        .color-option.active {
            border-color: var(--text-primary);
            transform: scale(1.2);
        }

        .color-option.active::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 10px;
            font-weight: 700;
            text-shadow: 0 0 4px rgba(0,0,0,0.5);
        }

        .color-blue { background: var(--primary-blue); }
        .color-purple { background: var(--primary-purple); }
        .color-green { background: var(--primary-green); }
        .color-orange { background: var(--primary-orange); }
        .color-pink { background: var(--primary-pink); }
        .color-teal { background: var(--primary-teal); }

        /* User Menu mejorado */
        .user-menu {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-lg);
            cursor: pointer;
            
            border: 1px solid transparent;
        }

        .user-menu:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-primary);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: var(--shadow-sm);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .user-role {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.3;
        }

        /* SIDEBAR CORREGIDO - Alineación perfecta */
        .main-sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-primary);
            border-right: 1px solid var(--border-primary);
            transition: var(--transition-slow);
            z-index: 999;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .main-sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .main-sidebar.collapsed:hover {
            width: var(--sidebar-width);
            box-shadow: var(--shadow-xl);
            z-index: 1001;
        }

        .sidebar-content {
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding: var(--space-md) 0;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin: 0;
            padding: 0;
        }

        .menu-header {
            padding: var(--space-lg) var(--space-xl);
            color: var(--text-tertiary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 1;
            
        }

        .main-sidebar.collapsed .menu-header {
            opacity: 0;
            padding: var(--space-sm) 0;
        }

        .main-sidebar.collapsed:hover .menu-header {
            opacity: 1;
            padding: var(--space-lg) var(--space-xl);
        }

        .menu-item {
            position: relative;
            margin: 0 var(--space-md);
        }

        /* MENU LINK CORREGIDO - Alineación perfecta en ambos estados */
        .menu-link {
            display: flex;
            align-items: center;
            padding: var(--space-md) var(--space-lg);
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            
            font-size: 15px;
            font-weight: 500;
            position: relative;
            min-height: 48px;
            width: 100%;
        }

        /* Estado colapsado - centrado perfecto */
        .main-sidebar.collapsed .menu-link {
            padding: var(--space-md);
            justify-content: center;
            width: calc(var(--sidebar-collapsed) - 2 * var(--space-md));
            margin: 0 auto;
        }

        /* Estado colapsado con hover - volver a expandido */
        .main-sidebar.collapsed:hover .menu-link {
            padding: var(--space-md) var(--space-lg);
            justify-content: flex-start;
            width: 100%;
            margin: 0;
        }

        .menu-link:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            transform: translateX(2px);
        }

        .main-sidebar.collapsed .menu-link:hover {
            transform: scale(1.05);
        }

        .main-sidebar.collapsed:hover .menu-link:hover {
            transform: translateX(2px);
        }

        .menu-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: var(--shadow-md);
        }

        .menu-link.active::before {
            content: '';
            position: absolute;
            left: -var(--space-md);
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* MENU ICON CORREGIDO - Centrado perfecto */
        .menu-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: var(--space-md);
            font-size: 16px;
            flex-shrink: 0;
        }

        .main-sidebar.collapsed .menu-icon {
            margin-right: 0;
            font-size: 18px;
            width: 20px;
            height: 20px;
        }

        .main-sidebar.collapsed:hover .menu-icon {
            margin-right: var(--space-md);
            font-size: 16px;
        }

        /* MENU TEXT CORREGIDO */
        .menu-text {
            flex: 1;
            opacity: 1;
            
            white-space: nowrap;
            overflow: hidden;
        }

        .main-sidebar.collapsed .menu-text {
            opacity: 0;
            width: 0;
        }

        .main-sidebar.collapsed:hover .menu-text {
            opacity: 1;
            width: auto;
        }

        .menu-arrow {
            margin-left: auto;
            font-size: 12px;
            
            opacity: 1;
            flex-shrink: 0;
        }

        .main-sidebar.collapsed .menu-arrow {
            opacity: 0;
            width: 0;
        }

        .main-sidebar.collapsed:hover .menu-arrow {
            opacity: 1;
            width: auto;
        }

        .menu-item.has-submenu.open .menu-arrow {
            transform: rotate(90deg);
        }

        /* SUBMENU CORREGIDO */
        .submenu {
            list-style: none;
            margin: var(--space-sm) 0 0 0;
            padding-left: var(--space-2xl);
            overflow: hidden;
            max-height: 0;
            transition: var(--transition-slow);
        }

        .menu-item.has-submenu.open .submenu {
            max-height: 500px;
        }

        .submenu .menu-item {
            margin: 0;
        }

        .submenu .menu-link {
            padding: var(--space-sm) var(--space-md);
            font-size: 14px;
            font-weight: 400;
            min-height: 40px;
            margin: 0;
        }

        .main-sidebar.collapsed .submenu {
            display: none;
        }

        .main-sidebar.collapsed:hover .submenu {
            display: block;
        }

        .content-wrapper {
            position: fixed;
            top: var(--header-height);
            left: var(--sidebar-width);
            right: 0;
            bottom: 0;
            padding: var(--space-lg);
            overflow-y: auto;
            overflow-x: hidden;
            transition: var(--transition-slow);
            background: var(--bg-secondary);
        }

        .main-sidebar.collapsed + .content-wrapper {
            left: var(--sidebar-collapsed);
        }

        /* CRÍTICO: Asegurar que el content-wrapper se adapte al sidebar colapsado */
        body:has(.main-sidebar.collapsed) .content-wrapper,
        .main-sidebar.collapsed ~ .content-wrapper {
            left: var(--sidebar-collapsed);
        }

        .content-header {
            background: var(--bg-primary);
            padding: var(--space-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-lg);
            border: 1px solid var(--border-primary);
            box-shadow: var(--shadow-sm);
        }

        .content-header h1 {
            color: var(--text-primary);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: var(--space-sm);
            letter-spacing: -0.025em;
        }

        .breadcrumb {
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 500;
        }

        /* Cards mejoradas */
        .card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
            border: 1px solid var(--border-primary);
            
            box-shadow: var(--shadow-sm);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-outline {
            border-top: 3px solid var(--primary);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-primary);
            padding: 0 0 var(--space-lg) 0;
            margin-bottom: var(--space-xl);
        }

        .card-title {
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 0;
            letter-spacing: -0.025em;
        }

        .card-body {
            padding: 0;
        }

        .card-tools {
            float: right;
            margin-right: -12px;
        }

        /* Forms mejorados */
        .form-group {
            margin-bottom: var(--space-lg);
        }

        .form-label {
            margin-bottom: var(--space-md);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            letter-spacing: 0.025em;
            text-transform: uppercase;
        }

        .form-control {
            display: block;
            width: 100%;
            height: 48px;
            padding: var(--space-md);
            font-size: 16px;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-primary);
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md);
            
            font-family: inherit;
        }

        .form-control:focus {
            color: var(--text-primary);
            background: var(--bg-primary);
            border-color: var(--primary);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-tertiary);
        }

        /* Input Groups */
        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        .input-group-prepend {
            display: flex;
        }

        .input-group-text {
            display: flex;
            align-items: center;
            padding: var(--space-md);
            font-size: 16px;
            font-weight: 500;
            color: var(--text-secondary);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md) 0 0 var(--radius-md);
            white-space: nowrap;
        }

        .input-group .form-control {
            border-left: 0;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
        }

        /* Buttons mejorados */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-xl);
            font-size: 15px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            text-decoration: none;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            
            font-family: inherit;
            letter-spacing: 0.025em;
            min-height: 44px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            background: transparent;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary.active {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
            border-color: var(--success);
            box-shadow: var(--shadow-sm);
        }

        .btn-success:hover {
            background: #0d9f73;
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
            box-shadow: var(--shadow-sm);
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
            border-color: var(--warning);
            box-shadow: var(--shadow-sm);
        }

        .btn-warning:hover {
            background: #d97706;
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Tamaños de botones */
        .btn-sm {
            padding: var(--space-sm) var(--space-lg);
            font-size: 14px;
            min-height: 36px;
        }

        .btn-xs {
            padding: var(--space-xs) var(--space-md);
            font-size: 13px;
            min-height: 28px;
        }

        .btn-lg {
            padding: var(--space-lg) var(--space-2xl);
            font-size: 17px;
            min-height: 52px;
        }

        /* Tables mejoradas */
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            color: var(--text-primary);
            border-collapse: separate;
            border-spacing: 0;
            font-size: 15px;
        }

        .table th,
        .table td {
            padding: var(--space-md) var(--space-lg);
            vertical-align: middle;
            border-bottom: 1px solid var(--border-primary);
        }

        .table thead th {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-primary);
            padding: var(--space-lg) var(--space-lg);
        }

        .table tbody tr {
            
        }

        .table tbody tr:hover {
            background: var(--bg-tertiary);
        }

        /* Alerts mejoradas */
        .alert {
            position: relative;
            padding: var(--space-xl);
            margin-bottom: var(--space-xl);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 15px;
            font-weight: 500;
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-left-color: var(--warning);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-left-color: var(--info);
        }

        /* Badges mejoradas */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: var(--space-sm) var(--space-md);
            font-size: 12px;
            font-weight: 600;
            border-radius: var(--radius-sm);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge-primary { background: var(--primary); color: white; }
        .badge-success { background: var(--success); color: white; }
        .badge-warning { background: var(--warning); color: white; }
        .badge-danger { background: var(--danger); color: white; }
        .badge-info { background: var(--info); color: white; }
        .badge-secondary { background: var(--text-secondary); color: white; }

        /* Utility Classes */
        .d-flex { display: flex !important; }
        .d-block { display: block !important; }
        .d-inline-block { display: inline-block !important; }
        .d-none { display: none !important; }
        .justify-content-between { justify-content: space-between !important; }
        .justify-content-center { justify-content: center !important; }
        .justify-content-end { justify-content: flex-end !important; }
        .align-items-center { align-items: center !important; }
        .align-items-end { align-items: flex-end !important; }
        .flex-wrap { flex-wrap: wrap !important; }
        .flex-column { flex-direction: column !important; }

        /* Margins */
        .mb-4 { margin-bottom: var(--space-2xl) !important; }
        .mb-3 { margin-bottom: var(--space-xl) !important; }
        .mb-2 { margin-bottom: var(--space-lg) !important; }
        .mb-1 { margin-bottom: var(--space-md) !important; }
        .mr-1 { margin-right: var(--space-xs) !important; }
        .mr-2 { margin-right: var(--space-sm) !important; }
        .mr-3 { margin-right: var(--space-md) !important; }
        .ml-1 { margin-left: var(--space-xs) !important; }
        .ml-2 { margin-left: var(--space-sm) !important; }
        .ml-3 { margin-left: var(--space-md) !important; }
        .mt-3 { margin-top: var(--space-xl) !important; }
        .m-0 { margin: 0 !important; }
        .m-3 { margin: var(--space-xl) !important; }
        .ml-auto { margin-left: auto !important; }

        /* Paddings */
        .p-0 { padding: 0 !important; }
        .p-1 { padding: var(--space-xs) !important; }
        .p-2 { padding: var(--space-sm) !important; }
        .p-3 { padding: var(--space-md) !important; }
        .p-4 { padding: var(--space-lg) !important; }
        .py-5 { padding-top: var(--space-2xl) !important; padding-bottom: var(--space-2xl) !important; }
        .py-4 { padding-top: var(--space-xl) !important; padding-bottom: var(--space-xl) !important; }
        .pl-4 { padding-left: var(--space-xl) !important; }

        /* Text */
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .text-dark { color: var(--text-primary) !important; }
        .text-muted { color: var(--text-tertiary) !important; }
        .text-primary { color: var(--primary) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-success { color: var(--success) !important; }
        .text-warning { color: var(--warning) !important; }
        .text-info { color: var(--info) !important; }
        .text-white { color: white !important; }
        .text-uppercase { text-transform: uppercase !important; }
        .text-sm { font-size: 14px !important; }
        .text-xs { font-size: 13px !important; }
        .font-weight-bold { font-weight: 700 !important; }
        .small { font-size: 14px !important; }

        /* Shadows y effects */
        .shadow { box-shadow: var(--shadow-lg) !important; }
        .shadow-sm { box-shadow: var(--shadow-sm) !important; }
        .invisible { visibility: hidden !important; }
        .fade { transition: opacity var(--transition); }
        .fade:not(.show) { opacity: 0; }
        .collapse:not(.show) { display: none; }

        /* Grid System mejorado */
        .row { 
            display: flex; 
            flex-wrap: wrap; 
            margin-right: -15px;
            margin-left: -15px; 
        }
        .col-md-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-8 { 
            padding-right: 15px;
            padding-left: 15px; 
        }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; }
        .col-md-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
        .col-md-5 { flex: 0 0 41.666667%; max-width: 41.666667%; }

        @media (max-width: 767.98px) {
            .col-md-12, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-8 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Responsive mejorado */
        @media (max-width: 768px) {
            html {
                font-size: 14px;
            }

            body:has(.main-sidebar.collapsed) .content-wrapper,
            .main-sidebar.collapsed ~ .content-wrapper {
                left: 0;
            }

            .main-header {
                padding: 0 var(--space-md);
            }

            .content-wrapper {
                left: 0;
                right: 0;
                padding: var(--space-sm);
            }

            .content-header {
                padding: var(--space-xl);
                margin-bottom: var(--space-xl);
            }

            .content-header h1 {
                font-size: 24px;
            }

            .card {
                padding: var(--space-md);
                margin-bottom: var(--space-md);
            }

            .main-sidebar {
                transform: translateX(-100%);
            }

            .main-sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                z-index: 998;
                display: none;
                backdrop-filter: blur(8px);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .header-right .color-palette {
                display: none;
            }

            .user-info {
                display: none;
            }

            .brand-text {
                display: none;
            }

            .btn {
                padding: var(--space-sm) var(--space-md);
                font-size: 14px;
                min-height: 40px;
            }

            .form-control {
                height: 44px;
                font-size: 16px; /* Evita zoom en iOS */
            }
        }

        /* Animations mejoradas */
        .fade-in {
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            border: 0.3em solid rgba(79, 70, 229, 0.25);
            border-right-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.75s linear infinite !important;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Focus States mejorados */
        .focus-ring:focus {
            outline: 2px solid var(--primary);
            outline-offset: 3px;
        }

        /* Modal Optimizations mejoradas */
        .modal {
            backdrop-filter: none !important;
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            display: none;
        }

        .modal-dialog {
            transition: transform 0.2s ease-out !important;
            transform: translateY(0) !important;
            will-change: transform;
        }

        .modal.fade .modal-dialog {
            transform: translateY(-40px) !important;
        }

        .modal.show .modal-dialog {
            transform: translateY(0) !important;
        }

        .modal-content {
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            animation: none !important;
            transform: none !important;
            transition: none !important;
            will-change: auto;
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border-bottom: 1px solid var(--border-primary);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
            padding: var(--space-lg);
            transition: none !important;
        }

        .modal-title {
            color: white !important;
            font-weight: 600;
            font-size: 20px;
            margin: 0;
            transition: none !important;
        }

        .modal-body {
            background: var(--bg-primary);
            color: var(--text-primary);
            padding: var(--space-xl);
            transition: none !important;
            transform: none !important;
            font-size: 15px;
            line-height: 1.6;
        }

        .modal-footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-primary);
            border-radius: 0 0 var(--radius-xl) var(--radius-xl);
            padding: var(--space-lg);
            transition: none !important;
        }

        .modal-header .close {
            background: transparent;
            border: none;
            font-size: 24px;
            font-weight: 300;
            color: white !important;
            opacity: 0.8;
            transition: opacity 0.2s ease !important;
            padding: 0;
            margin: 0;
            line-height: 1;
            text-shadow: none;
        }

        .modal-header .close:hover {
            opacity: 1;
            color: white !important;
            transform: scale(1.1);
        }

        .modal-header .close:focus {
            outline: none;
            box-shadow: none;
        }

        .modal * {
            animation: none !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease !important;
        }

        .modal-lg {
            max-width: 900px;
        }

        .modal-xl {
            max-width: 1200px;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--border-primary);
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--text-tertiary);
        }

        /* Responsive modal adjustments */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: var(--space-md);
                max-width: calc(100% - 24px);
            }

            .modal-body {
                max-height: 60vh;
                padding: var(--space-lg);
            }

            .modal-header,
            .modal-footer {
                padding: var(--space-md);
            }

            .modal-title {
                font-size: 18px;
            }

            #modalPrevisualizacion .modal-body {
                height: 70vh;
                padding: var(--space-md) !important;
            }
        }

        /* Dropdown mejorado */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            display: none;
            float: left;
            min-width: 160px;
            padding: var(--space-sm) 0;
            margin: var(--space-xs) 0 0;
            font-size: 14px;
            text-align: left;
            list-style: none;
            background-color: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .dropdown.show .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: var(--space-md) var(--space-xl);
            clear: both;
            font-weight: 400;
            color: var(--text-primary);
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background: transparent;
            border: 0;
            border-radius: var(--radius-md);
            margin: var(--space-xs) 0;
            
            font-size: 15px;
        }

        .dropdown-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            transform: translateX(4px);
        }

        /* Livewire Compatibility */
        [wire\:loading] {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Botones de herramientas mejorados */
        .btn-tool {
            background: transparent;
            border: none;
            color: var(--text-tertiary);
            cursor: pointer;
            font-size: 15px;
            padding: var(--space-md);
            border-radius: var(--radius-md);
            
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-tool:hover {
            color: var(--primary);
            background: var(--bg-tertiary);
            transform: scale(1.05);
        }

        /* Button Groups mejorados */
        .btn-group {
            position: relative;
            display: inline-flex;
            vertical-align: middle;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .btn-group > .btn {
            position: relative;
            flex: 1 1 auto;
            border-radius: 0;
        }

        .btn-group > .btn:first-child {
            border-radius: var(--radius-md) 0 0 var(--radius-md);
        }

        .btn-group > .btn:last-child {
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
        }

        .btn-group > .btn:not(:first-child) {
            margin-left: -1px;
        }

        /* Elementos específicos mejorados */
        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .cartera-info {
            display: flex;
            flex-direction: column;
            gap: var(--space-sm);
        }

        .cartera-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .cartera-name {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .text-mora {
            color: var(--danger) !important;
        }

        .btn-group-sm .btn {
            padding: var(--space-xs) var(--space-sm);
            font-size: 12px;
            min-height: 32px;
        }

        .footer-total {
            background: var(--bg-tertiary);
            font-weight: 600;
        }

        .footer-total td {
            padding: var(--space-md) var(--space-lg);
            border-top: 2px solid var(--border-primary);
        }

        .cuota-detalle {
            transition: none !important;
        }

        .toggle-cliente {
            transition: transform 0.2s ease !important;
        }

        .toggle-cliente.expanded i {
            transform: rotate(90deg);
        }

        .btn-xs {
            min-width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        #contador-clientes {
            font-size: 22px;
            font-weight: 700;
        }

        /* Mejoras adicionales para elementos específicos */
        .border-left-0 { border-left: 0 !important; }
        .border-right-0 { border-right: 0 !important; }
        .bg-white { background-color: var(--bg-primary) !important; }
        .flex-fill { flex: 1 1 auto !important; }

        /* Efectos de hover mejorados */
        .card-header .btn,
        .content-header .btn {
            margin-bottom: 0;
        }

        .content-header .d-flex .btn {
            flex-shrink: 0;
        }

        /* Micro-interacciones */
        .menu-link,
        .btn,
        .form-control,
        .card {
            will-change: transform;
        }

        .menu-link:active,
        .btn:active {
            transform: scale(0.98);
        }

        /* Estados de éxito y error más visibles */
        .alert {
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: currentColor;
        }

        /* Mejoras en contraste para accesibilidad */
        .text-muted {
            color: var(--gray-500) !important;
        }

        [data-theme="dark"] .text-muted {
            color: var(--gray-400) !important;
        }

        /* Scrollbar en contenido principal */
        .content-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .content-wrapper::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .content-wrapper::-webkit-scrollbar-thumb {
            background: var(--border-primary);
            border-radius: 4px;
        }

        /* Botón flotante de asistencia */
        .attendance-float-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: var(--transition);
            opacity: 0.9;
        }

        .attendance-float-button:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            opacity: 1;
        }

        .attendance-float-button:active {
            transform: translateY(-1px) scale(1.05);
        }

        /* Menú desplegable del botón flotante */
        .attendance-menu {
            position: fixed;
            bottom: 100px;
            right: 30px;
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--space-sm);
            z-index: 998;
            opacity: 0;
            transform: scale(0.9) translateY(10px);
            pointer-events: none;
            transition: var(--transition);
            backdrop-filter: blur(20px);
            min-width: 200px;
        }

        .attendance-menu.show {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: all;
        }

        .attendance-menu-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-md) var(--space-lg);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: var(--transition);
            font-size: 15px;
            font-weight: 500;
            margin-bottom: var(--space-xs);
            cursor: pointer;
            border: 1px solid transparent;
        }

        .attendance-menu-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
            transform: translateX(4px);
            border-color: var(--border-primary);
        }

        .attendance-menu-item.entry {
            color: var(--success);
        }

        .attendance-menu-item.entry:hover {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .attendance-menu-item.break {
            color: var(--warning);
        }

        .attendance-menu-item.break:hover {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .attendance-menu-item.exit {
            color: var(--danger);
        }

        .attendance-menu-item.exit:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .attendance-menu-item.no-work {
            color: var(--info);
        }

        .attendance-menu-item.no-work:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }

        .attendance-menu-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .attendance-menu-text {
            flex: 1;
        }

        .attendance-menu-subtitle {
            font-size: 12px;
            color: var(--text-tertiary);
            margin-top: 2px;
        }

        /* Estado de asistencia actual */
        .attendance-status {
            position: fixed;
            top: 90px;
            right: 20px;
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            box-shadow: var(--shadow-md);
            z-index: 997;
            opacity: 0;
            transform: translateX(20px);
            pointer-events: none;
            transition: var(--transition);
            font-size: 13px;
            min-width: 180px;
        }

        .attendance-status.show {
            opacity: 1;
            transform: translateX(0);
            pointer-events: all;
        }

        .attendance-status-header {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-sm);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .attendance-status-time {
            color: var(--text-secondary);
            font-size: 12px;
        }

        .attendance-status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .attendance-status-indicator.active {
            background: var(--success);
            animation: pulse 2s infinite;
        }

        .attendance-status-indicator.break {
            background: var(--warning);
            animation: pulse 2s infinite;
        }

        .attendance-status-indicator.completed {
            background: #22c55e;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Notificación de acción exitosa */
        .attendance-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: 1000;
            opacity: 0;
            transform: translateX(300px);
            transition: var(--transition);
            font-weight: 500;
            max-width: 300px;
        }

        .attendance-notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .attendance-notification.error {
            background: var(--danger);
        }

        .attendance-notification.warning {
            background: var(--warning);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .attendance-float-button {
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                font-size: 20px;
            }

            .attendance-menu {
                bottom: 85px;
                right: 20px;
                left: 20px;
                width: auto;
            }

            .attendance-status {
                top: 80px;
                left: 20px;
                right: 20px;
                width: auto;
            }

            .attendance-notification {
                top: 80px;
                left: 20px;
                right: 20px;
                width: auto;
                max-width: none;
            }
        }

        /* Overlay para cerrar el menú */
        .attendance-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 996;
            background: transparent;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .attendance-overlay.show {
            opacity: 1;
            pointer-events: all;
        }

        /* Modal de confirmación */
        .attendance-confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
        }

        .attendance-confirm-modal.show {
            opacity: 1;
            pointer-events: all;
        }

        .attendance-confirm-content {
            background: var(--bg-primary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-2xl);
            padding: var(--space-xl);
            max-width: 400px;
            width: calc(100vw - 40px);
            transform: scale(0.9);
            transition: var(--transition);
        }

        .attendance-confirm-modal.show .attendance-confirm-content {
            transform: scale(1);
        }

        .attendance-confirm-header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .attendance-confirm-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .attendance-confirm-icon.entry {
            background: linear-gradient(135deg, var(--success), #16a34a);
        }

        .attendance-confirm-icon.break {
            background: linear-gradient(135deg, var(--warning), #d97706);
        }

        .attendance-confirm-icon.exit {
            background: linear-gradient(135deg, var(--danger), #dc2626);
        }

        .attendance-confirm-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .attendance-confirm-message {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: var(--space-xl);
        }

        .attendance-confirm-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: flex-end;
        }

        .attendance-confirm-btn {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-lg);
            font-size: 14px;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition);
            min-width: 100px;
            justify-content: center;
        }

        .attendance-confirm-btn.cancel {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border-color: var(--border-primary);
        }

        .attendance-confirm-btn.cancel:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-color: var(--border-secondary);
        }

        .attendance-confirm-btn.confirm {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .attendance-confirm-btn.confirm:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .attendance-confirm-btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body data-theme="light">
    <!-- Header -->
    <nav class="main-header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('admin.index') }}" class="brand">
                @if(isset($brandConfig['logo_path']) && $brandConfig['logo_path'])
                    <img src="{{ asset('storage/' . $brandConfig['logo_path']) }}" alt="Logo" class="brand-logo-img">
                @else
                    <div class="brand-logo-fallback">{{ substr($brandConfig['site_name'] ?? 'B', 0, 1) }}</div>
                @endif
                <span class="brand-text">{{ $brandConfig['site_name'] ?? 'Banking' }}</span>
            </a>
        </div>
        
        <div class="header-right">
            <div class="theme-toggle" id="themeToggle"></div>
            
            <div class="color-palette">
                <div class="color-option color-blue active" data-color="blue"></div>
                <div class="color-option color-purple" data-color="purple"></div>
                <div class="color-option color-green" data-color="green"></div>
                <div class="color-option color-orange" data-color="orange"></div>
                <div class="color-option color-pink" data-color="pink"></div>
                <div class="color-option color-teal" data-color="teal"></div>
            </div>

            <div class="dropdown">
                <div class="user-menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->codigo ?? 'Usuario' }}</div>
                        <div class="user-role">{{ Auth::user()->roles->pluck('name')->join(', ') ?: 'Usuario' }}</div>
                    </div>
                    <i class="fas fa-chevron-down" style="font-size: 12px; color: var(--text-tertiary);"></i>
                </div>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar" id="mainSidebar">
        <div class="sidebar-content">
            <ul class="sidebar-menu" id="sidebarMenu">
                @hasSection('sidebar_custom')
                    @yield('sidebar_custom')
                @else
                    {{-- Leer configuración del menú desde config/adminlte.php --}}
                    @php
                        $menuItems = config('adminlte.menu', []);
                    @endphp

                    @foreach($menuItems as $item)
                        @php
                            // Verificar permisos si existe 'can'
                            if (isset($item['can']) && !auth()->user()?->can($item['can'])) {
                                continue;
                            }
                            
                            // Saltar items del navbar
                            if (isset($item['topnav_right']) && $item['topnav_right']) {
                                continue;
                            }
                            
                            // Saltar items que no son del sidebar
                            if (isset($item['type'])) {
                                if (in_array($item['type'], ['navbar-search', 'fullscreen-widget'])) {
                                    continue;
                                }
                                if ($item['type'] === 'sidebar-menu-search') {
                                    // Por ahora saltamos la búsqueda del sidebar
                                    continue;
                                }
                            }
                        @endphp

                        @if(isset($item['header']))
                            {{-- Header del menú --}}
                            <li class="menu-header">
                                {{ $item['header'] }}
                            </li>
                        @elseif(isset($item['submenu']))
                            {{-- Elemento con submenú --}}
                            @php
                                $hasActiveSubmenu = false;
                                foreach($item['submenu'] as $subitem) {
                                    if (isset($subitem['route']) && request()->routeIs($subitem['route'])) {
                                        $hasActiveSubmenu = true;
                                        break;
                                    }
                                    if (isset($subitem['url'])) {
                                        $cleanUrl = trim($subitem['url'], '/');
                                        if (request()->is($cleanUrl) || request()->is($cleanUrl.'/*')) {
                                            $hasActiveSubmenu = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <li class="menu-item has-submenu {{ $hasActiveSubmenu ? 'open' : '' }}">
                                <a href="#" class="menu-link {{ $hasActiveSubmenu ? 'active' : '' }}" data-submenu="{{ Str::slug($item['text']) }}">
                                    <div class="menu-icon">
                                        @if(isset($item['icon']))
                                            <i class="{{ $item['icon'] }}"></i>
                                        @else
                                            <i class="fas fa-circle"></i>
                                        @endif
                                    </div>
                                    <span class="menu-text">{{ $item['text'] }}</span>
                                    <i class="fas fa-chevron-right menu-arrow"></i>
                                </a>
                                <ul class="submenu">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            // Verificar permisos del subitem
                                            if (isset($subitem['can']) && !auth()->user()?->can($subitem['can'])) {
                                                continue;
                                            }
                                            
                                            // Determinar URL del subitem
                                            $url = '#';
                                            $isActive = false;
                                            
                                            if (isset($subitem['route'])) {
                                                try {
                                                    $url = route($subitem['route']);
                                                    $isActive = request()->routeIs($subitem['route']);
                                                } catch (\Exception $e) {
                                                    // Si la ruta no existe, usar URL si está disponible
                                                    if (isset($subitem['url'])) {
                                                        $url = url($subitem['url']);
                                                    }
                                                }
                                            } elseif (isset($subitem['url'])) {
                                                $url = url($subitem['url']);
                                                $cleanUrl = trim($subitem['url'], '/');
                                                $isActive = request()->is($cleanUrl) || request()->is($cleanUrl.'/*');
                                            }
                                        @endphp
                                        <li class="menu-item">
                                            <a href="{{ $url }}" class="menu-link {{ $isActive ? 'active' : '' }}"
                                               @if(isset($subitem['target'])) target="{{ $subitem['target'] }}" @endif>
                                                <div class="menu-icon">
                                                    @if(isset($subitem['icon']))
                                                        <i class="{{ $subitem['icon'] }}"></i>
                                                    @else
                                                        <i class="far fa-circle"></i>
                                                    @endif
                                                </div>
                                                <span class="menu-text">{{ $subitem['text'] }}</span>
                                                @if(isset($subitem['label']))
                                                    <span class="badge badge-{{ $subitem['label_color'] ?? 'primary' }} ml-auto">{{ $subitem['label'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            {{-- Elemento simple del menú --}}
                            @php
                                // Determinar URL del item
                                $url = '#';
                                $isActive = false;
                                
                                if (isset($item['route'])) {
                                    try {
                                        $url = route($item['route']);
                                        $isActive = request()->routeIs($item['route']);
                                    } catch (\Exception $e) {
                                        // Si la ruta no existe, usar URL si está disponible
                                        if (isset($item['url'])) {
                                            $url = url($item['url']);
                                        }
                                    }
                                } elseif (isset($item['url'])) {
                                    $url = url($item['url']);
                                    $cleanUrl = trim($item['url'], '/');
                                    $isActive = request()->is($cleanUrl) || request()->is($cleanUrl.'/*');
                                }
                            @endphp
                            <li class="menu-item">
                                <a href="{{ $url }}" class="menu-link {{ $isActive ? 'active' : '' }}"
                                   @if(isset($item['target'])) target="{{ $item['target'] }}" @endif>
                                    <div class="menu-icon">
                                        @if(isset($item['icon']))
                                            <i class="{{ $item['icon'] }}"></i>
                                        @else
                                            <i class="fas fa-circle"></i>
                                        @endif
                                    </div>
                                    <span class="menu-text">{{ $item['text'] }}</span>
                                    @if(isset($item['label']))
                                        <span class="badge badge-{{ $item['label_color'] ?? 'primary' }} ml-auto">{{ $item['label'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Content -->
    <div class="content-wrapper" id="contentWrapper">
        @hasSection('content_header')
            <div class="content-header fade-in">
                <h1>@yield('title')</h1>
                @hasSection('breadcrumbs')
                    <div class="breadcrumb">@yield('breadcrumbs')</div>
                @endif
            </div>
        @endif
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle mr-2"></i> {!! session('success') !!}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning fade-in">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('warning') }}
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info fade-in">
                <i class="fas fa-info-circle mr-2"></i> {{ session('info') }}
            </div>
        @endif

        {{-- Main Content --}}
        <div class="fade-in">
            @yield('content')
        </div>
    </div>

    {{-- Logout Form --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Botón flotante de asistencia -->
    <div class="attendance-float-container" id="attendanceFloatContainer" style="display: none;">
        <!-- Overlay para cerrar menús -->
        <div class="attendance-overlay" id="attendanceOverlay"></div>
        
        <!-- Estado actual de asistencia -->
        <div class="attendance-status" id="attendanceStatus">
            <div class="attendance-status-header">
                <div class="attendance-status-indicator" id="attendanceIndicator"></div>
                <span id="attendanceStatusText">Sin registro</span>
            </div>
            <div class="attendance-status-time" id="attendanceStatusTime">--:--</div>
        </div>

        <!-- Menú desplegable -->
        <div class="attendance-menu" id="attendanceMenu">
            <div class="attendance-menu-item entry" data-action="entrada">
                <div class="attendance-menu-icon">
                    <i class="fas fa-play"></i>
                </div>
                <div class="attendance-menu-text">
                    Marcar Entrada
                    <div class="attendance-menu-subtitle">Iniciar jornada laboral</div>
                </div>
            </div>
            
            <div class="attendance-menu-item break" data-action="refrigerio-inicio">
                <div class="attendance-menu-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="attendance-menu-text">
                    Iniciar Refrigerio
                    <div class="attendance-menu-subtitle">Pausa de descanso</div>
                </div>
            </div>
            
            <div class="attendance-menu-item break" data-action="refrigerio-fin" style="display: none;">
                <div class="attendance-menu-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="attendance-menu-text">
                    Finalizar Refrigerio
                    <div class="attendance-menu-subtitle">Retomar trabajo</div>
                </div>
            </div>
            
            <div class="attendance-menu-item exit" data-action="salida">
                <div class="attendance-menu-icon">
                    <i class="fas fa-stop"></i>
                </div>
                <div class="attendance-menu-text">
                    Marcar Salida
                    <div class="attendance-menu-subtitle">Finalizar jornada</div>
                </div>
            </div>

            <!-- Opción para día no laboral -->
            <div class="attendance-menu-item no-work" data-action="ver-dia-no-laboral" style="display: none;">
                <div class="attendance-menu-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="attendance-menu-text">
                    Día de Descanso
                    <div class="attendance-menu-subtitle">Ver información completa</div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación integrado -->
        <div class="attendance-confirm-modal" id="attendanceConfirmModal">
            <div class="attendance-confirm-content">
                <div class="attendance-confirm-header">
                    <div class="attendance-confirm-icon" id="attendanceConfirmIcon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="attendance-confirm-title" id="attendanceConfirmTitle">
                        Confirmar Acción
                    </div>
                </div>
                <div class="attendance-confirm-message" id="attendanceConfirmMessage">
                    ¿Estás seguro de que deseas realizar esta acción?
                </div>
                <div class="attendance-confirm-actions">
                    <button class="attendance-confirm-btn cancel" id="attendanceConfirmCancel">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button class="attendance-confirm-btn confirm" id="attendanceConfirmAccept">
                        <i class="fas fa-check"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>

        <!-- Botón principal -->
        <button class="attendance-float-button" id="attendanceFloatButton" type="button" title="Marcar asistencia">
            <i class="fas fa-clock"></i>
        </button>
        
        <!-- Notificaciones -->
        <div class="attendance-notification" id="attendanceNotification"></div>
    </div>

    <!-- Scripts -->
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <script>
        // CSRF Token para AJAX
        window.Laravel = {!! json_encode(['csrfToken' => csrf_token()]) !!};

        // Color palettes
        const colorPalettes = {
            blue: { primary: '#3049a7', light: '#4a6bc8', dark: '#1e3a8a' },
            purple: { primary: '#7c3aed', light: '#8b5cf6', dark: '#5b21b6' },
            green: { primary: '#059669', light: '#10b981', dark: '#047857' },
            orange: { primary: '#ea580c', light: '#f97316', dark: '#c2410c' },
            pink: { primary: '#db2777', light: '#ec4899', dark: '#be185d' },
            teal: { primary: '#0891b2', light: '#06b6d4', dark: '#0e7490' }
        };

        // Estado del sistema
        const appState = {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            openSubmenus: JSON.parse(localStorage.getItem('openSubmenus') || '[]'),
            theme: localStorage.getItem('theme') || 'light',
            colorScheme: localStorage.getItem('colorScheme') || 'blue'
        };

        // Elementos DOM
        const body = document.body;
        const sidebar = document.getElementById('mainSidebar');
        const contentWrapper = document.getElementById('contentWrapper');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const themeToggle = document.getElementById('themeToggle');
        const colorOptions = document.querySelectorAll('.color-option');

        // Inicializar aplicación
        function initializeApp() {
            // Aplicar tema
            applyTheme(appState.theme);
            
            // Aplicar esquema de color
            applyColorScheme(appState.colorScheme);
            
            // Aplicar estado del sidebar
            if (appState.sidebarCollapsed) {
                sidebar.classList.add('collapsed');
            }

            // Restaurar submenús abiertos
            appState.openSubmenus.forEach(submenuId => {
                const menuItem = document.querySelector(`[data-submenu="${submenuId}"]`)?.parentElement;
                if (menuItem) {
                    menuItem.classList.add('open');
                }
            });

            // Auto-abrir submenú del elemento activo
            const activeLink = document.querySelector('.menu-link.active');
            if (activeLink && activeLink.closest('.submenu')) {
                const parentSubmenu = activeLink.closest('.menu-item.has-submenu');
                if (parentSubmenu) {
                    parentSubmenu.classList.add('open');
                }
            }

            // Inicializar sistema de asistencia
            initializeAttendanceSystem();
        }

        // Aplicar tema
        function applyTheme(theme) {
            body.setAttribute('data-theme', theme);
            themeToggle.classList.toggle('dark', theme === 'dark');
            appState.theme = theme;
            localStorage.setItem('theme', theme);
        }

        // Aplicar esquema de color
        function applyColorScheme(scheme) {
            const colors = colorPalettes[scheme];
            if (colors) {
                document.documentElement.style.setProperty('--primary', colors.primary);
                document.documentElement.style.setProperty('--primary-light', colors.light);
                document.documentElement.style.setProperty('--primary-dark', colors.dark);
                
                // Actualizar estado activo
                colorOptions.forEach(option => {
                    option.classList.toggle('active', option.dataset.color === scheme);
                });
                
                appState.colorScheme = scheme;
                localStorage.setItem('colorScheme', scheme);
            }
        }

        // Toggle sidebar - CRÍTICO: Actualizar content-wrapper
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            appState.sidebarCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', appState.sidebarCollapsed);

            // En móvil, mostrar/ocultar overlay
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
        }

        // Manejar submenús - Solo un submenú abierto a la vez
        function toggleSubmenu(submenuId, menuItem) {
            const isOpen = menuItem.classList.contains('open');
            
            if (isOpen) {
                // Cerrar el submenú actual
                menuItem.classList.remove('open');
                appState.openSubmenus = appState.openSubmenus.filter(id => id !== submenuId);
            } else {
                // Cerrar todos los otros submenús primero
                document.querySelectorAll('.menu-item.has-submenu.open').forEach(openItem => {
                    if (openItem !== menuItem) {
                        openItem.classList.remove('open');
                    }
                });
                
                // Abrir el submenú actual
                menuItem.classList.add('open');
                // Solo mantener el submenú actual abierto
                appState.openSubmenus = [submenuId];
            }
            
            localStorage.setItem('openSubmenus', JSON.stringify(appState.openSubmenus));
        }

        // Event Listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        themeToggle.addEventListener('click', () => {
            const newTheme = appState.theme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
        });

        colorOptions.forEach(option => {
            option.addEventListener('click', () => {
                const scheme = option.dataset.color;
                applyColorScheme(scheme);
            });
        });

        // Manejar clics en el menú
        document.addEventListener('click', (e) => {
            const menuLink = e.target.closest('.menu-link');
            if (!menuLink) return;

            const submenuId = menuLink.getAttribute('data-submenu');

            if (submenuId) {
                // Es un enlace con submenú
                e.preventDefault();
                toggleSubmenu(submenuId, menuLink.parentElement);
            }
        });

        // Manejar dropdown del usuario
        document.addEventListener('click', (e) => {
            const dropdown = e.target.closest('.dropdown');
            const userMenu = e.target.closest('.user-menu');
            
            if (userMenu) {
                e.preventDefault();
                e.stopPropagation();
                
                // Cerrar otros dropdowns
                document.querySelectorAll('.dropdown.show').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });
                
                // Toggle el dropdown actual
                dropdown.classList.toggle('show');
            } else if (!dropdown) {
                // Cerrar todos los dropdowns si se hace clic fuera
                document.querySelectorAll('.dropdown.show').forEach(d => d.classList.remove('show'));
            }
        });

        // Responsive handling
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // AdminLTE compatibility: Card widget collapse
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-card-widget="collapse"]') || e.target.closest('[data-card-widget="collapse"]')) {
                e.preventDefault();
                const button = e.target.closest('[data-card-widget="collapse"]');
                const card = button.closest('.card');
                const cardBody = card.querySelector('.card-body');
                const icon = button.querySelector('i');
                
                if (cardBody) {
                    if (cardBody.style.display === 'none') {
                        cardBody.style.display = 'block';
                        icon.className = 'fas fa-minus';
                    } else {
                        cardBody.style.display = 'none';
                        icon.className = 'fas fa-plus';
                    }
                }
            }
        });

        // Livewire Compatibility
        document.addEventListener('livewire:navigated', () => {
            // Reinicializar eventos después de navegación de Livewire
            initializeApp();
        });

        // Livewire Loading Enhancement
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-alert', (data) => {
                // Crear alerta dinámica
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${data.type} fade-in`;
                alertDiv.innerHTML = `
                    <i class="fas fa-${data.type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>
                    ${data.message}
                `;
                
                // Insertar al inicio del content-wrapper
                const contentWrapper = document.querySelector('.content-wrapper');
                if (contentWrapper) {
                    contentWrapper.insertBefore(alertDiv, contentWrapper.firstChild);
                    
                    // Auto-hide después de 5 segundos
                    setTimeout(() => {
                        alertDiv.style.opacity = '0';
                        alertDiv.style.transform = 'translateY(-10px)';
                        setTimeout(() => alertDiv.remove(), 300);
                    }, 5000);
                }
            });
        });

        // Livewire Error Handling
        document.addEventListener('livewire:exception', (event) => {
            console.error('Livewire Exception:', event.detail);
        });

        // Modal enhancements - Optimizado para diseño compacto
        document.addEventListener('show.bs.modal', function (event) {
            const modal = event.target;
            
            // Desactivar transiciones temporalmente
            document.body.style.transition = 'none';
            modal.style.transition = 'opacity 0.15s linear';
            
            // Forzar reflow
            modal.offsetHeight;
            
            // Prevenir animaciones en elementos internos
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.animation = 'none';
                modalContent.style.transform = 'none';
                modalContent.style.transition = 'none';
            }
        });

        document.addEventListener('shown.bs.modal', function (event) {
            const modal = event.target;
            
            // Restaurar transiciones
            setTimeout(() => {
                document.body.style.transition = '';
                
                // Auto-focus primer input en el modal
                const firstInput = modal.querySelector('.form-control:not([readonly]):not([disabled])');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        });

        document.addEventListener('hide.bs.modal', function (event) {
            const modal = event.target;
            modal.style.transition = 'opacity 0.15s linear';
            
            // Asegurar que no haya interferencias
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.animation = 'none';
                modalContent.style.transform = 'none';
            }
        });

        document.addEventListener('hidden.bs.modal', function (event) {
            const modal = event.target;
            
            // Cleanup completo
            modal.style.transition = '';
            document.body.style.transition = '';
            
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.animation = '';
                modalContent.style.transform = '';
                modalContent.style.transition = '';
            }
        });

        // Función helper para acordeones dinámicos - SIMPLIFICADA Y CORREGIDA
        window.initializeAccordionEvents = function() {
            console.log('Inicializando eventos del acordeón...');
            
            // REMOVER eventos anteriores para evitar duplicados
            $(document).off('click', '.cliente-header, .toggle-cliente');
            $(document).off('click', '#expand-all');
            
            // Eventos de toggle individual con delegación
            $(document).on('click', '.cliente-header, .toggle-cliente', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const clienteId = $(this).data('cliente') || $(this).closest('[data-cliente]').data('cliente');
                console.log('Toggle cliente desde initializeAccordionEvents:', clienteId);
                
                if (clienteId) {
                    window.toggleCliente(clienteId);
                }
            });

            // Expandir/contraer todo con delegación
            $(document).on('click', '#expand-all', function(e) {
                e.preventDefault();
                
                const isExpanded = $(this).hasClass('expanded');
                console.log('Expandir/contraer todo. Estado actual expandido:', isExpanded);
                
                if (!isExpanded) {
                    // Expandir todos
                    $('.cuota-detalle').removeClass('d-none');
                    $('.toggle-cliente i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    $('.toggle-cliente').addClass('expanded');
                    $(this).addClass('expanded');
                    $(this).html('<i class="fas fa-compress-arrows-alt"></i> Contraer Todo');
                } else {
                    // Contraer todos
                    $('.cuota-detalle').addClass('d-none');
                    $('.toggle-cliente i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    $('.toggle-cliente').removeClass('expanded');
                    $(this).removeClass('expanded');
                    $(this).html('<i class="fas fa-expand-arrows-alt"></i> Expandir Todo');
                }
            });
        };

        // Función para toggle individual - CORREGIDA
        window.toggleCliente = function(clienteId) {
            console.log('toggleCliente ejecutado para cliente:', clienteId);
            
            const detalles = $(`.cuota-detalle[data-cliente="${clienteId}"]`);
            const boton = $(`.toggle-cliente[data-cliente="${clienteId}"]`);
            const icono = boton.find('i');
            
            console.log('Detalles encontrados:', detalles.length);
            console.log('Botón encontrado:', boton.length);
            
            if (detalles.length && boton.length) {
                if (detalles.hasClass('d-none')) {
                    // Mostrar detalles
                    detalles.removeClass('d-none');
                    icono.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    boton.addClass('expanded');
                    console.log('Cliente expandido:', clienteId);
                } else {
                    // Ocultar detalles
                    detalles.addClass('d-none');
                    icono.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    boton.removeClass('expanded');
                    console.log('Cliente contraído:', clienteId);
                }
            } else {
                console.error('No se encontraron elementos para cliente:', clienteId);
            }
        };

        // FUNCIÓN CRÍTICA: verPrevisualizacion - DEBE ESTAR DISPONIBLE GLOBALMENTE
        window.verPrevisualizacion = function(cuotaId) {
            console.log('verPrevisualizacion ejecutado con cuota:', cuotaId);
            
            // Variable global para almacenar el ID de la cuota actual
            window.cuotaActualId = cuotaId;
            
            // Verificar que el modal existe
            const modal = $('#modalPrevisualizacion');
            if (modal.length === 0) {
                console.error('Modal de previsualización no encontrado');
                alert('Error: Modal de previsualización no disponible');
                return;
            }
            
            console.log('Mostrando modal para cuota:', cuotaId);
            
            // Mostrar el modal
            modal.modal('show');
            
            // Resetear el estado del modal
            $('#loading-preview').show();
            $('#contenido-preview').hide();
            $('#error-preview').hide();
            $('#btn-descargar-pdf').prop('disabled', true);
            $('#btn-imprimir').prop('disabled', true);
            
            // Construir la URL correctamente
            const baseUrl = "{{ route('admin.deudas.previsualizacion-estado-cobranza', '') }}";
            const url = `${baseUrl}/${cuotaId}`;
            console.log('URL de previsualización:', url);
            
            // Cargar la previsualización via AJAX
            $.ajax({
                url: url,
                type: 'GET',
                timeout: 30000, // 30 segundos de timeout
                success: function(response) {
                    console.log('Previsualización cargada exitosamente');
                    $('#loading-preview').hide();
                    $('#contenido-preview').html(response).show();
                    $('#btn-descargar-pdf').prop('disabled', false);
                    $('#btn-imprimir').prop('disabled', false);
                    
                    // Actualizar el título con información del cliente
                    const clienteNombre = window.extraerNombreCliente(response);
                    if (clienteNombre) {
                        $('#modalPrevisualizacionLabel').html(`
                            <i class="fas fa-file-pdf mr-2"></i>
                            Estado de Cobranza - ${clienteNombre}
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar previsualización:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        error: error,
                        responseText: xhr.responseText
                    });
                    
                    $('#loading-preview').hide();
                    $('#error-preview').show();
                    
                    // Mostrar detalles del error en consola para debugging
                    if (xhr.responseText) {
                        console.error('Respuesta del servidor:', xhr.responseText);
                    }
                }
            });
        };

        // Función para extraer el nombre del cliente del HTML de respuesta
        window.extraerNombreCliente = function(html) {
            try {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const elemento = doc.querySelector('.user-name b');
                return elemento ? elemento.textContent.trim() : null;
            } catch (e) {
                console.error('Error al extraer nombre del cliente:', e);
                return null;
            }
        };

        // CONFIGURAR EVENTOS DEL MODAL - DEBE EJECUTARSE UNA SOLA VEZ
        $(document).ready(function() {
            console.log('Configurando eventos del modal...');

            // Evento para descargar PDF desde el modal
            $(document).off('click', '#btn-descargar-pdf').on('click', '#btn-descargar-pdf', function() {
                if (window.cuotaActualId) {
                    const baseUrl = "{{ route('admin.deudas.descargar-estado-cobranza', '') }}";
                    const downloadUrl = `${baseUrl}/${window.cuotaActualId}`;
                    console.log('Descargando PDF desde:', downloadUrl);
                    window.open(downloadUrl, '_blank');
                } else {
                    console.error('No hay cuota actual para descargar');
                }
            });

            // Evento para imprimir el contenido de la modal
            $(document).off('click', '#btn-imprimir').on('click', '#btn-imprimir', function() {
                const contenido = document.getElementById('contenido-preview');
                
                if (!contenido || contenido.innerHTML.trim() === '') {
                    alert('No hay contenido para imprimir. Por favor, espera a que cargue la previsualización.');
                    return;
                }
                
                // Crear una nueva ventana para imprimir
                const ventanaImpresion = window.open('', '_blank');
                ventanaImpresion.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Estado de Cobranza</title>
                        <style>
                            body { 
                                font-family: Arial, sans-serif; 
                                margin: 0; 
                                padding: 15px;
                                color: #333;
                                background: white;
                                font-size: 12px;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 15px;
                            }
                            table th, table td {
                                border: 1px solid #ddd;
                                padding: 6px;
                                text-align: left;
                                font-size: 11px;
                            }
                            table th {
                                background-color: #1e4a72;
                                color: white;
                                font-weight: bold;
                            }
                            .info-card {
                                border: 1px solid #ddd;
                                padding: 10px;
                                border-radius: 4px;
                                margin-bottom: 10px;
                            }
                            .status-badge, .dia-badge {
                                background: #31bed8;
                                color: white;
                                padding: 2px 6px;
                                border-radius: 10px;
                                font-size: 10px;
                            }
                            .team-member {
                                border: 1px solid #ddd;
                                padding: 6px;
                                margin-bottom: 4px;
                                border-radius: 4px;
                            }
                            @media print {
                                body { margin: 0; padding: 10px; }
                                .no-print { display: none !important; }
                                table { page-break-inside: avoid; }
                                .info-card { page-break-inside: avoid; }
                            }
                        </style>
                    </head>
                    <body>
                        ${contenido.innerHTML}
                    </body>
                    </html>
                `);
                
                ventanaImpresion.document.close();
                
                // Esperar a que cargue y luego imprimir
                ventanaImpresion.onload = function() {
                    setTimeout(function() {
                        ventanaImpresion.print();
                        ventanaImpresion.close();
                    }, 250);
                };
            });

            // Limpiar variables al cerrar el modal
            $('#modalPrevisualizacion').on('hidden.bs.modal', function () {
                console.log('Modal cerrado, limpiando variables...');
                window.cuotaActualId = null;
                $('#contenido-preview').empty();
                $('#modalPrevisualizacionLabel').html('<i class="fas fa-file-pdf mr-2"></i>Previsualización - Estado de Cobranza');
            });
        });

        // Auto-inicializar acordeones cuando se carga contenido dinámico
        $(document).on('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - inicializando acordeones...');
            if (typeof window.initializeAccordionEvents === 'function') {
                window.initializeAccordionEvents();
            }
        });

        // FUNCIONES LEGACY PARA COMPATIBILIDAD
        window.verDetalles = function(cuotaId) {
            console.log('verDetalles (legacy) ejecutado con cuota:', cuotaId);
            return window.verPrevisualizacion(cuotaId);
        };

        window.confirmarDescargaPDF = function(url, nombreCliente) {
            if (confirm('¿Deseas descargar el estado de cobranza de ' + nombreCliente + '?')) {
                window.open(url, '_blank');
            }
            return false;
        };

        // ====================================
        // SISTEMA DE ASISTENCIA FLOTANTE
        // ====================================

        // Estado global de asistencia
        const attendanceState = {
            currentStatus: null, // 'entrada', 'refrigerio', 'salida', 'no-laboral', 'completado'
            lastAction: null,
            lastActionTime: null,
            isBreakActive: false,
            isMenuOpen: false,
            dayCompleted: false,
            isStatusVisible: false,
            nonWorkingDayData: null // Datos cuando es día no laboral
        };

        // Elementos de asistencia
        const attendanceElements = {
            container: null,
            button: null,
            menu: null,
            overlay: null,
            status: null,
            notification: null,
            statusText: null,
            statusTime: null,
            statusIndicator: null,
            confirmModal: null,
            confirmIcon: null,
            confirmTitle: null,
            confirmMessage: null,
            confirmCancel: null,
            confirmAccept: null
        };

        // Inicializar sistema de asistencia
        function initializeAttendanceSystem() {
            // Obtener elementos
            attendanceElements.container = document.getElementById('attendanceFloatContainer');
            attendanceElements.button = document.getElementById('attendanceFloatButton');
            attendanceElements.menu = document.getElementById('attendanceMenu');
            attendanceElements.overlay = document.getElementById('attendanceOverlay');
            attendanceElements.status = document.getElementById('attendanceStatus');
            attendanceElements.notification = document.getElementById('attendanceNotification');
            attendanceElements.statusText = document.getElementById('attendanceStatusText');
            attendanceElements.statusTime = document.getElementById('attendanceStatusTime');
            attendanceElements.statusIndicator = document.getElementById('attendanceIndicator');
            attendanceElements.confirmModal = document.getElementById('attendanceConfirmModal');
            attendanceElements.confirmIcon = document.getElementById('attendanceConfirmIcon');
            attendanceElements.confirmTitle = document.getElementById('attendanceConfirmTitle');
            attendanceElements.confirmMessage = document.getElementById('attendanceConfirmMessage');
            attendanceElements.confirmCancel = document.getElementById('attendanceConfirmCancel');
            attendanceElements.confirmAccept = document.getElementById('attendanceConfirmAccept');

            // Verificar si todos los elementos existen
            if (!attendanceElements.container || !attendanceElements.button) {
                console.log('Sistema de asistencia no disponible en esta página');
                return;
            }

            // Solo mostrar el botón flotante si el usuario tiene permisos de asistencia
            // o si estamos en secciones relacionadas con asistencia
            const hasAttendancePermission = checkAttendancePermission();
            const isAttendancePage = checkIfAttendancePage();

            if (hasAttendancePermission || isAttendancePage) {
                attendanceElements.container.style.display = 'block';
                setupAttendanceEvents();
                loadCurrentAttendanceStatus();
                startAttendancePolling();
            }
        }

        // Verificar permisos de asistencia (personalizar según el sistema de roles)
        function checkAttendancePermission() {
            // Solo mostrar el botón flotante de asistencia en el dashboard principal
            // NO en páginas de gestión administrativa como crear usuarios
            const currentPath = window.location.pathname;
            return (currentPath === '/admin' || currentPath === '/admin/dashboard') && {{ Auth::check() ? 'true' : 'false' }};
        }

        // Verificar si estamos en páginas relacionadas con asistencia
        function checkIfAttendancePage() {
            const currentPath = window.location.pathname;
            // Solo cargar en páginas específicas de asistencia, no en gestión de usuarios, etc.
            return currentPath.includes('/admin/asistencia') || 
                   currentPath === '/admin' ||
                   currentPath === '/admin/dashboard';
        }

        // Configurar eventos de asistencia
        function setupAttendanceEvents() {
            // Click en botón principal
            attendanceElements.button.addEventListener('click', toggleAttendanceMenu);

            // Click en overlay para cerrar
            attendanceElements.overlay.addEventListener('click', closeAttendanceMenu);

            // Click en opciones del menú
            const menuItems = attendanceElements.menu.querySelectorAll('.attendance-menu-item');
            menuItems.forEach(item => {
                item.addEventListener('click', handleAttendanceAction);
            });

            // Eventos del modal de confirmación
            if (attendanceElements.confirmCancel) {
                attendanceElements.confirmCancel.addEventListener('click', closeAttendanceConfirmModal);
            }
            if (attendanceElements.confirmAccept) {
                attendanceElements.confirmAccept.addEventListener('click', confirmAttendanceAction);
            }

            // Hover en botón para mostrar estado
            attendanceElements.button.addEventListener('mouseenter', showAttendanceStatus);
            attendanceElements.button.addEventListener('mouseleave', hideAttendanceStatus);

            // Cerrar menú con tecla Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (attendanceElements.confirmModal && attendanceElements.confirmModal.classList.contains('show')) {
                        closeAttendanceConfirmModal();
                    } else if (attendanceState.isMenuOpen) {
                        closeAttendanceMenu();
                    }
                }
            });
        }

        // Toggle del menú de asistencia
        function toggleAttendanceMenu() {
            if (attendanceState.isMenuOpen) {
                closeAttendanceMenu();
            } else {
                openAttendanceMenu();
            }
        }

        // Abrir menú de asistencia
        function openAttendanceMenu() {
            attendanceState.isMenuOpen = true;
            attendanceElements.menu.classList.add('show');
            attendanceElements.overlay.classList.add('show');
            updateMenuOptions();
            hideAttendanceStatus();
        }

        // Cerrar menú de asistencia
        function closeAttendanceMenu() {
            attendanceState.isMenuOpen = false;
            attendanceElements.menu.classList.remove('show');
            attendanceElements.overlay.classList.remove('show');
        }

        // Mostrar estado de asistencia
        function showAttendanceStatus() {
            if (!attendanceState.isMenuOpen) {
                attendanceState.isStatusVisible = true;
                attendanceElements.status.classList.add('show');
            }
        }

        // Ocultar estado de asistencia
        function hideAttendanceStatus() {
            attendanceState.isStatusVisible = false;
            attendanceElements.status.classList.remove('show');
        }

        // Variable global para almacenar la acción pendiente
        let pendingAttendanceAction = null;

        // Manejar acciones de asistencia
        function handleAttendanceAction(event) {
            event.preventDefault();
            event.stopPropagation();

            const action = event.currentTarget.dataset.action;
            if (!action) return;

            // Acción especial para ver día no laboral
            if (action === 'ver-dia-no-laboral') {
                closeAttendanceMenu();
                if (attendanceState.nonWorkingDayData && attendanceState.nonWorkingDayData.redirect_to) {
                    window.location.href = attendanceState.nonWorkingDayData.redirect_to;
                }
                return;
            }

            // Guardar acción pendiente
            pendingAttendanceAction = action;

            // Mostrar modal de confirmación
            showAttendanceConfirmModal(action);
            closeAttendanceMenu();
        }

        // Mostrar modal de confirmación
        function showAttendanceConfirmModal(action) {
            if (!attendanceElements.confirmModal) return;

            const actionData = getActionData(action);
            
            // Configurar ícono
            const iconElement = attendanceElements.confirmIcon.querySelector('i');
            if (iconElement) {
                iconElement.className = actionData.icon;
            }
            attendanceElements.confirmIcon.className = `attendance-confirm-icon ${actionData.type}`;
            
            // Configurar título y mensaje
            if (attendanceElements.confirmTitle) {
                attendanceElements.confirmTitle.textContent = actionData.title;
            }
            if (attendanceElements.confirmMessage) {
                attendanceElements.confirmMessage.textContent = actionData.message;
            }

            // Mostrar modal
            attendanceElements.confirmModal.classList.add('show');
        }

        // Cerrar modal de confirmación
        function closeAttendanceConfirmModal() {
            if (!attendanceElements.confirmModal) return;
            
            attendanceElements.confirmModal.classList.remove('show');
            pendingAttendanceAction = null;
        }

        // Confirmar acción de asistencia
        function confirmAttendanceAction() {
            if (!pendingAttendanceAction) return;

            // Ejecutar acción
            executeAttendanceAction(pendingAttendanceAction);
            
            // Cerrar modal
            closeAttendanceConfirmModal();
        }

        // Obtener datos de la acción
        function getActionData(action) {
            const actionMap = {
                'entrada': {
                    type: 'entry',
                    icon: 'fas fa-play',
                    title: 'Marcar Entrada',
                    message: '¿Confirmas que deseas marcar tu entrada? Se registrará la hora actual y tu ubicación.'
                },
                'refrigerio-inicio': {
                    type: 'break',
                    icon: 'fas fa-coffee',
                    title: 'Iniciar Refrigerio',
                    message: '¿Confirmas que deseas iniciar tu refrigerio? El tiempo será cronometrado automáticamente.'
                },
                'refrigerio-fin': {
                    type: 'break',
                    icon: 'fas fa-play-circle',
                    title: 'Finalizar Refrigerio',
                    message: '¿Confirmas que deseas finalizar tu refrigerio? Se calculará el tiempo total de descanso.'
                },
                'salida': {
                    type: 'exit',
                    icon: 'fas fa-stop',
                    title: 'Marcar Salida',
                    message: '¿Confirmas que deseas marcar tu salida? Se finalizará tu jornada laboral y se calcularán las horas trabajadas.'
                }
            };
            
            return actionMap[action] || {
                type: 'entry',
                icon: 'fas fa-question-circle',
                title: 'Confirmar Acción',
                message: '¿Estás seguro de que deseas realizar esta acción?'
            };
        }

        // Ejecutar acción de asistencia
        function executeAttendanceAction(action) {
            showAttendanceNotification('Procesando...', 'info');

            // Obtener posición GPS si es disponible
            const executeWithLocation = (coords) => {
                const actionData = {
                    action: action,
                    timestamp: new Date().toISOString(),
                    latitude: coords?.latitude || null,
                    longitude: coords?.longitude || null,
                    user_agent: navigator.userAgent,
                    timezone: 'America/Lima'
                };

                // Enviar datos al servidor
                sendAttendanceAction(actionData);
            };

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => executeWithLocation(position.coords),
                    (error) => {
                        console.warn('No se pudo obtener la ubicación:', error);
                        executeWithLocation(null);
                    },
                    { timeout: 5000, enableHighAccuracy: false }
                );
            } else {
                executeWithLocation(null);
            }
        }

        // Enviar acción al servidor
        function sendAttendanceAction(data) {
            fetch('{{ route("admin.asistencia.marcar") ?? "/admin/asistencia/marcar" }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.Laravel?.csrfToken || ''
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                // Obtener el JSON de la respuesta, independientemente del estado
                return response.json().then(result => {
                    if (response.ok) {
                        return { success: true, data: result };
                    } else {
                        return { success: false, data: result, status: response.status };
                    }
                });
            })
            .then(responseData => {
                if (responseData.success) {
                    handleAttendanceSuccess(responseData.data);
                } else {
                    // Manejar errores del servidor
                    const errorData = responseData.data;
                    
                    // Verificar si es día completado
                    if (errorData.day_completed) {
                        showAttendanceNotification(
                            errorData.message || '✅ Jornada laboral completada',
                            'success'
                        );
                        return; // Salir para evitar procesar más errores
                    }
                    
                    // Verificar si es un día no laboral
                    if (errorData.is_non_working_day) {
                        showAttendanceNotification(
                            errorData.message || 'Hoy es día de descanso',
                            'warning'
                        );
                        
                        // Redirigir después de un breve delay
                        setTimeout(() => {
                            if (errorData.redirect_to) {
                                window.location.href = errorData.redirect_to;
                            }
                        }, 2000);
                    } else {
                        showAttendanceNotification(
                            `Error: ${errorData.message || 'Error desconocido'}`,
                            'error'
                        );
                    }
                }
            })
            .catch(error => {
                console.error('Error al marcar asistencia:', error);
                showAttendanceNotification(
                    `Error de conexión: ${error.message}`,
                    'error'
                );
            });
        }

        // Manejar respuesta exitosa
        function handleAttendanceSuccess(result) {
            // Actualizar estado local
            attendanceState.currentStatus = result.current_status;
            attendanceState.lastAction = result.action;
            attendanceState.lastActionTime = result.timestamp;
            attendanceState.isBreakActive = result.is_break_active;
            attendanceState.dayCompleted = result.day_completed || false;

            // Actualizar UI
            updateAttendanceDisplay();
            showAttendanceNotification(result.message, 'success');

            // Mostrar estado por unos segundos
            showAttendanceStatus();
            setTimeout(hideAttendanceStatus, 3000);
        }

        // Cargar estado actual de asistencia
        function loadCurrentAttendanceStatus() {
            fetch('{{ route("admin.asistencia.estado-actual") ?? "/admin/asistencia/estado" }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.Laravel?.csrfToken || ''
                }
            })
            .then(response => {
                return response.json().then(result => {
                    if (response.ok) {
                        return { success: true, data: result };
                    } else {
                        return { success: false, data: result, status: response.status };
                    }
                });
            })
            .then(responseData => {
                if (responseData.success && responseData.data.success) {
                    attendanceState.currentStatus = responseData.data.current_status;
                    attendanceState.lastAction = responseData.data.last_action;
                    attendanceState.lastActionTime = responseData.data.last_action_time;
                    attendanceState.isBreakActive = responseData.data.is_break_active;
                    attendanceState.dayCompleted = responseData.data.day_completed || false;
                    
                    // Si hay un mensaje de día completado, mostrarlo
                    if (attendanceState.dayCompleted && responseData.data.message) {
                        showAttendanceNotification(responseData.data.message, 'success');
                    }
                    
                    updateAttendanceDisplay();
                } else {
                    // Verificar si es un día no laboral
                    const errorData = responseData.data;
                    if (errorData && errorData.is_non_working_day) {
                        // Configurar estado para día no laboral
                        attendanceState.currentStatus = 'no-laboral';
                        attendanceState.lastAction = 'no-laboral';
                        attendanceState.lastActionTime = null;
                        attendanceState.isBreakActive = false;
                        attendanceState.nonWorkingDayData = errorData;
                        
                        updateAttendanceDisplay();
                        console.log('Hoy es día de descanso - mostrando en sistema flotante');
                        return;
                    }
                    
                    // Mostrar estado por defecto para otros errores
                    updateAttendanceDisplay();
                }
            })
            .catch(error => {
                console.log('Estado de asistencia no disponible:', error);
                // Mostrar estado por defecto
                updateAttendanceDisplay();
            });
        }

        // Actualizar la visualización de asistencia
        function updateAttendanceDisplay() {
            updateStatusDisplay();
            updateButtonIcon();
            updateMenuOptions();
        }

        // Actualizar el display de estado
        function updateStatusDisplay() {
            let statusText = 'Sin registro';
            let statusTime = '--:--';
            let indicatorClass = '';

            if (attendanceState.lastActionTime) {
                const actionTime = new Date(attendanceState.lastActionTime);
                statusTime = actionTime.toLocaleTimeString('es-PE', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            }

            switch (attendanceState.currentStatus) {
                case 'entrada':
                    statusText = attendanceState.isBreakActive ? 'En refrigerio' : 'Trabajando';
                    indicatorClass = attendanceState.isBreakActive ? 'break' : 'active';
                    break;
                case 'salida':
                    statusText = attendanceState.dayCompleted ? 'Jornada completada' : 'Jornada finalizada';
                    indicatorClass = attendanceState.dayCompleted ? 'completed' : '';
                    break;
                case 'no-laboral':
                    statusText = 'Día de descanso';
                    statusTime = 'No laboral';
                    indicatorClass = '';
                    break;
                default:
                    statusText = 'Sin registro';
                    indicatorClass = '';
            }

            if (attendanceElements.statusText) {
                attendanceElements.statusText.textContent = statusText;
            }
            if (attendanceElements.statusTime) {
                attendanceElements.statusTime.textContent = statusTime;
            }
            if (attendanceElements.statusIndicator) {
                attendanceElements.statusIndicator.className = 'attendance-status-indicator ' + indicatorClass;
            }
        }

        // Actualizar ícono del botón
        function updateButtonIcon() {
            const iconElement = attendanceElements.button?.querySelector('i');
            if (!iconElement) return;

            let iconClass = 'fas fa-clock';
            
            if (attendanceState.currentStatus === 'no-laboral') {
                iconClass = 'fas fa-bed';
            } else if (attendanceState.isBreakActive) {
                iconClass = 'fas fa-coffee';
            } else if (attendanceState.currentStatus === 'entrada') {
                iconClass = 'fas fa-user-clock';
            } else if (attendanceState.currentStatus === 'salida') {
                iconClass = 'fas fa-user-check';
            }

            iconElement.className = iconClass;
        }

        // Actualizar opciones del menú
        function updateMenuOptions() {
            const menuItems = attendanceElements.menu?.querySelectorAll('.attendance-menu-item');
            if (!menuItems) return;

            menuItems.forEach(item => {
                const action = item.dataset.action;
                let shouldShow = false;

                // Si es día no laboral, solo mostrar la opción de ver información
                if (attendanceState.currentStatus === 'no-laboral') {
                    shouldShow = action === 'ver-dia-no-laboral';
                } else if (attendanceState.dayCompleted) {
                    // Si el día está completado, no mostrar ninguna opción
                    shouldShow = false;
                } else {
                    // Lógica normal para días laborales
                    switch (action) {
                        case 'entrada':
                            shouldShow = !attendanceState.currentStatus || attendanceState.currentStatus === 'salida';
                            break;
                        case 'refrigerio-inicio':
                            shouldShow = attendanceState.currentStatus === 'entrada' && !attendanceState.isBreakActive;
                            break;
                        case 'refrigerio-fin':
                            shouldShow = attendanceState.currentStatus === 'entrada' && attendanceState.isBreakActive;
                            break;
                        case 'salida':
                            shouldShow = attendanceState.currentStatus === 'entrada';
                            break;
                        case 'ver-dia-no-laboral':
                            shouldShow = false; // Solo se muestra en días no laborales
                            break;
                    }
                }

                item.style.display = shouldShow ? 'flex' : 'none';
            });
        }

        // Mostrar notificación
        function showAttendanceNotification(message, type = 'success') {
            if (!attendanceElements.notification) return;

            attendanceElements.notification.textContent = message;
            attendanceElements.notification.className = `attendance-notification ${type} show`;

            // Auto-ocultar después de 3 segundos
            setTimeout(() => {
                attendanceElements.notification.classList.remove('show');
            }, 3000);
        }

        // Polling para actualizar estado cada 5 minutos
        function startAttendancePolling() {
            setInterval(loadCurrentAttendanceStatus, 5 * 60 * 1000); // 5 minutos
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', initializeApp);

        // Smooth scroll para navegación
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                // Validar que el href no sea solo "#" y sea un selector válido
                if (href && href.length > 1 && href !== '#') {
                    e.preventDefault();
                    try {
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    } catch (e) {
                        // Ignorar selectores inválidos silenciosamente
                        console.warn('Invalid selector for smooth scroll:', href);
                    }
                }
            });
        });
    </script>

    @yield('js')
</body>
</html>