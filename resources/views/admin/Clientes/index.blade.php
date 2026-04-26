@extends('layouts.admin')

@section('title', 'Clientes')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-hand-holding-usd mr-2"></i>Gestión de Clientes</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Clientes</li>
       </ol>
   </div>
@stop

@section('content')
    @if (session('info'))
        <div class="alert alert-success" role="alert">
            {{ session('info') }}
        </div>
    @endif
    @if (session('status'))
        @if (session('error_message'))
        <div class="alert alert-danger" role="alert">
            {{ session('status') }}. {{ session('error_message') }}.
        </div>
        @else
            <div class="alert alert-success" role="alert">
                {{ session('status') }}.
            </div>
        @endif
    @endif
    @livewire('clientes.show-clientes')
@stop

@section('css')
    <!-- <link rel="stylesheet" href="/css/admin_custom.css"> -->
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop