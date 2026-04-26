<!-- Resumen por Usuario -->
<div class="col-12">
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-users me-2"></i>Resumen por Usuario</h3>
        </div>
        <div class="card-body">
            @if(count($resumenUsuarios) > 0)
                <div class="row g-3">
                    @foreach($resumenUsuarios as $usuario)
                        <div class="col-lg-3 col-md-2 col-sm-6">
                            <div class="info-card" style="position: relative;">
                                <!-- Header del usuario -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="info-label">
                                            <i class="fas fa-user me-1"></i>{{ $usuario['codigo'] }}
                                        </div>
                                        <small class="text-muted">{{ $usuario['nombre'] }}</small>
                                    </div>
                                    @if($usuario['efectivo_por_rendir'] > 0)
                                        <span class="badge bg-warning">Pendiente</span>
                                    @else
                                        <span class="badge bg-success">Al día</span>
                                    @endif
                                </div>

                                <!-- Gráfico -->
                                <div class="text-center mb-3">
                                    <div style="position: relative; height: 80px; width: 80px; margin: 0 auto;">
                                        <canvas id="chartUser{{ $loop->index }}" width="80" height="80"></canvas>
                                    </div>
                                </div>

                                <!-- Información detallada -->
                                <div class="row g-1 text-center">
                                    <div class="col-6">
                                        <small class="text-success d-block">Rendido</small>
                                        <strong class="text-success">S/ {{ number_format($usuario['efectivo_rendido'], 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-warning d-block">Pendiente</small>
                                        <strong class="text-warning">S/ {{ number_format($usuario['efectivo_por_rendir'], 2) }}</strong>
                                    </div>
                                </div>

                                <hr class="my-2">

                                <div class="row g-1 text-center">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Otros métodos</small>
                                        <span class="text-muted">S/ {{ number_format($usuario['otros_metodos'], 2) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">% Rendido</small>
                                        <span class="text-dark fw-bold">{{ $usuario['porcentaje_rendido'] }}%</span>
                                    </div>
                                </div>

                                <!-- Botón de acción -->
                                @if($usuario['efectivo_por_rendir'] > 0)
                                    <div class="mt-3">
                                        <a href="{{ route('admin.caja.mostrarRendicionUsuario', $usuario['user_id']) }}" 
                                           class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-hand-holding-usd me-1"></i>
                                            Gestionar Rendición
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay datos de usuarios con los filtros aplicados</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Función para inicializar gráficos de usuarios
function initUserCharts(resumenUsuarios) {
    // Destruir gráficos existentes
    if (window.userCharts) {
        Object.values(window.userCharts).forEach(chart => chart.destroy());
    }
    window.userCharts = {};

    // Crear nuevos gráficos
    resumenUsuarios.forEach((usuario, index) => {
        const canvas = document.getElementById('chartUser' + index);
        if (canvas) {
            const totalEfectivo = usuario.efectivo_rendido + usuario.efectivo_por_rendir;
            
            if (totalEfectivo > 0) {
                window.userCharts[index] = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Rendido', 'Pendiente'],
                        datasets: [{
                            data: [usuario.efectivo_rendido, usuario.efectivo_por_rendir],
                            backgroundColor: ['#28a745', '#ffc107'],
                            borderColor: ['#ffffff', '#ffffff'],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return context.label + ': S/ ' + value.toFixed(2) + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            } else {
                // Si no hay efectivo, mostrar un mensaje
                const ctx = canvas.getContext('2d');
                ctx.font = '10px Arial';
                ctx.fillStyle = '#6c757d';
                ctx.textAlign = 'center';
                ctx.fillText('Solo otros', canvas.width/2, canvas.height/2 - 5);
                ctx.fillText('métodos', canvas.width/2, canvas.height/2 + 5);
            }
        }
    });
}

// Los botones ahora son enlaces directos, no necesitan JavaScript adicional
</script>