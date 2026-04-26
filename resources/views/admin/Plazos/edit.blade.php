@extends('layouts.admin')
@section('title', 'Editar Plazo')
@section('content')
    <div class="container pt-2">
        <h1 class="text-2xl font-bold mb-4">Editar Plazo</h1>
        <form action="{{ route('admin.plazos.update', $plazo) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-gray-600 font-bold">Nombre</label>
                <input type="text" name="nombre" class="form-input w-full" value="{{ old('nombre', $plazo->nombre) }}" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 font-bold">Duración (semanas)</label>
                <input type="number" name="duracion_semanas" class="form-input w-full" value="{{ old('duracion_semanas', $plazo->duracion_semanas) }}" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 font-bold">Tasas Asociadas</label>
                <select name="tasa_ids[]" class="choices-select w-full" multiple required>
                    @foreach($tasas as $tasa)
                        <option value="{{ $tasa->id }}" {{ $plazo->plazosByTasa->pluck('tasa_id')->contains($tasa->id) ? 'selected' : '' }}>
                            {{ $tasa->tipo_tasa }} - {{ $tasa->valor }}%
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Actualizar</button>
        </form>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        new Choices(document.querySelector('.choices-select'), { removeItemButton: true });
    </script>
@endsection