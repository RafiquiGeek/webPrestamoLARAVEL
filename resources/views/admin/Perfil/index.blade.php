@extends('layouts.admin')

@section('title', 'Perfil')

@section('content_header')
    <h1 class="m-0 text-dark">Perfil</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="nombres">Nombres</label>
                        <input type="text" class="form-control" name="nombres" id="nombres">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="no">Email</label>
                        <input type="email" class="form-control" name="email" id="email">
                    </div>
                </div>
            </div>
        </div>
    </div>    
    

@stop

@section('css')
    <!-- <link rel="stylesheet" href="/css/admin_custom.css"> -->
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop

