@extends('layouts.admin')

@section('title', 'Asignar Aval')

@section('content')
<div class="container-fluid">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card card-outline">
				<div class="card-header d-flex justify-content-between align-items-center">
					<h3 class="card-title mb-0">
						<i class="fas fa-user-shield mr-2"></i>
						Asignar Aval
					</h3>
					<a href="{{ route('admin.prestamos.show', $prestamo->id ?? request('prestamo_id')) }}" class="btn btn-secondary btn-sm">
						<i class="fas fa-arrow-left mr-1"></i>
						Volver al Préstamo
					</a>
				</div>
				<div class="card-body">
					<form method="POST" action="{{ route('admin.prestamos.asignarAvalById', $prestamo->id ?? request('prestamo_id')) }}" id="formAsignarAval">
						@csrf
						<div class="row">
							<div class="col-md-12 mb-3">
								<label for="inputDni" class="form-label">DNI del Aval</label>
								<div class="input-group">
									<input type="text" class="form-control" placeholder="Ingresa el DNI" id="inputDni" name="aval_id" maxlength="8">
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="button" id="btnValidarDni">
											<i class="fas fa-search mr-1"></i>
											Verificar
										</button>
										<button class="btn btn-primary ms-2" type="submit" id="btnGuardarAval">
										<i class="fas fa-save mr-1"></i>
										Guardar
										</button>
									</div>
								</div>
								<div class="invalid-feedback">El DNI debe contener 8 dígitos numéricos.</div>
							</div>

							<div class="col-md-12 mb-3">
								<label class="form-label">Nombre del Aval</label>
								<div class="form-control bg-white">
									<span id="nombreCliente" class="text-primary font-weight-bold">---</span>
								</div>
							</div>

							<div class="col-md-12 mb-3" id="avalDetalles" style="display: none;">
								<div class="card border-0 bg-light">
									<div class="card-body p-3">
										<h6 class="card-title text-dark mb-2">
											<i class="fas fa-info-circle me-2"></i>Detalle del Aval
										</h6>
										<div id="avalInfo"></div>
									</div>
								</div>
							</div>

							<div class="col-md-12 mb-3">
								<label for="parentesco" class="form-label">Parentesco</label>
								<input type="text" name="parentesco" id="parentesco" class="form-control" placeholder="Ej. Hermano, Madre">
							</div>

							<div class="col-md-12 mb-3">
								<label for="observaciones" class="form-label">Observaciones</label>
								<textarea name="observaciones" id="observaciones" class="form-control" rows="3" placeholder="Detalles adicionales (opcional)"></textarea>
							</div>

							<input type="hidden" id="hiddenAvalId" name="aval_id_hidden" />
						</div>
						</form>
					</div>
			</div>
		</div>
	</div>
</div>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Asignar aval (misma lógica usada en Solicitudes/create.blade.php)
	const btnValidar = document.getElementById('btnValidarDni');
	const dniInput = document.getElementById('inputDni');
	const nombreCliente = document.getElementById('nombreCliente');
	const avalDetalles = document.getElementById('avalDetalles');
	const avalInfo = document.getElementById('avalInfo');
	const hiddenAval = document.getElementById('hiddenAvalId');

	if (dniInput) {
		dniInput.addEventListener('input', function() {
			this.classList.remove('is-invalid');
		});
	}

	if (btnValidar) {
		btnValidar.addEventListener('click', function() {
			const dniValue = dniInput.value.trim();
			if (dniValue.length !== 8 || !/^\d+$/.test(dniValue)) {
				dniInput.classList.add('is-invalid');
				nombreCliente.textContent = 'DNI inválido';
				return;
			}

			fetch("{{ route('admin.prestamos.validarAvalAntesDeAsignar') }}", {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': '{{ csrf_token() }}'
				},
				body: JSON.stringify({ aval_id: dniValue })
			})
			.then(response => response.json())
			.then(data => {
				if (data.error) {
					nombreCliente.textContent = 'No encontrado';
					avalDetalles.style.display = 'none';
					hiddenAval.value = '';
					alert(data.error);
				} else {
					nombreCliente.textContent = data.nombreAval || 'Sin nombre';
					hiddenAval.value = dniValue;

					let avalInfoHtml = '';
					if (data.es_cliente) avalInfoHtml += '<p><strong>Es cliente:</strong> Sí</p>';
					if (data.tieneDeuda) avalInfoHtml += '<div class="alert alert-warning">Tiene cuotas vencidas</div>';
					if (!avalInfoHtml) avalInfoHtml = '<div class="alert alert-info">Sin observaciones</div>';

					avalInfo.innerHTML = avalInfoHtml;
					avalDetalles.style.display = 'block';
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('No se pudo verificar el aval.');
			});
		});
	}

	// Al enviar el formulario, si no se verificó, usamos el valor del input principal
	const form = document.getElementById('formAsignarAval');
	if (form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();

			// Si hiddenAval está vacío, tomar el valor del campo
			if (!hiddenAval.value) {
				hiddenAval.value = dniInput.value.trim();
			}

			if (hiddenAval.value.length !== 8 || !/^\d+$/.test(hiddenAval.value)) {
				dniInput.classList.add('is-invalid');
				nombreCliente.textContent = 'DNI inválido';
				return false;
			}

			const payload = {
				aval_id: hiddenAval.value,
				parentesco: document.getElementById('parentesco') ? document.getElementById('parentesco').value : '',
				observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
			};

			fetch("{{ route('admin.prestamos.asignarAvalById', $prestamo->id ?? (request('prestamo_id') ?? '')) }}", {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': '{{ csrf_token() }}'
				},
				body: JSON.stringify(payload)
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					// Mostrar éxito y redirigir al show del préstamo
					Swal.fire({
						icon: 'success',
						title: 'Asignado',
						text: data.success || data.message || 'Aval asignado correctamente',
						timer: 2000,
						showConfirmButton: false
					}).then(() => {
						window.location.href = "{{ route('admin.prestamos.show', $prestamo->id ?? (request('prestamo_id') ?? '')) }}";
					});
				} else {
					Swal.fire('Error', data.error || 'No se pudo asignar el aval', 'error');
				}
			})
			.catch(err => {
				console.error(err);
				Swal.fire('Error', 'No se pudo asignar el aval', 'error');
			});

			return false;
		});
	}
});
</script>
@stop
