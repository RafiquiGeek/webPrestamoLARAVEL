@extends('adminlte::page')

@section('title', 'Debug Cliente 987 - Préstamo 2073')

@section('content_header')
    <h1>Debug Cliente 987 - Préstamo 2073</h1>
    <p class="text-muted">Pantalla especial para verificar la sincronización de sucursales</p>
@stop

@section('content')
    <div class="container-fluid">
        @livewire('debug.cliente-987-debug')
    </div>
@stop

@section('css')
    <style>
        .debug-card {
            transition: all 0.3s ease;
        }
        .debug-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
@stop

@section('js')
    <script>
        console.log('🔍 Debug Page Loaded - Cliente 987 & Préstamo 2073');
    </script>
@stop