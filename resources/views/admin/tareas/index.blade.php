@extends('adminlte::page')

@section('title', 'Tablero de Tareas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Tablero de Tareas Kanban</h1>
    </div>
@stop

@section('content')
    <livewire:tablero-kanban />
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            min-height: calc(100vh - 250px);
        }

        .kanban-column {
            flex: 0 0 320px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            height: fit-content;
            min-height: 400px;
        }

        .kanban-column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid;
        }

        .kanban-column-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .kanban-cards {
            min-height: 100px;
        }

        .kanban-card {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            cursor: grab;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            transition: all 0.2s;
            border-left: 4px solid;
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .kanban-card.sortable-ghost {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        .kanban-card.sortable-drag {
            cursor: grabbing;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .task-priority {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-baja {
            background: #d4edda;
            color: #155724;
        }

        .priority-media {
            background: #fff3cd;
            color: #856404;
        }

        .priority-alta {
            background: #ffeeba;
            color: #ff6b35;
        }

        .priority-urgente {
            background: #f8d7da;
            color: #721c24;
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .task-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .add-task-btn {
            width: 100%;
            border: 2px dashed #dee2e6;
            background: transparent;
            color: #6c757d;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .add-task-btn:hover {
            border-color: #007bff;
            color: #007bff;
            background: rgba(0,123,255,0.05);
        }

        .sidebar-right {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }

        .sidebar-right.show {
            right: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1049;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .progress-bar-custom {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 0.5rem;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .file-item {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }

        .file-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }

        .file-item .file-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #6c757d;
        }

        .comment-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .comment-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .comment-text {
            margin-top: 0.25rem;
            font-size: 0.9rem;
        }

        .modal {
            z-index: 1050;
        }

        .modal-backdrop {
            z-index: 1040;
        }

        .modal-dialog {
            z-index: 1060;
        }

        .archivo-item {
            position: relative;
            transition: all 0.3s ease;
        }

        .archivo-item:hover {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 5px;
        }

        .archivo-item .btn-danger {
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .archivo-item:hover .btn-danger {
            opacity: 1;
        }

        .archivo-item .btn-danger:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        #archivos-container .form-control {
            border: 2px dashed #dee2e6;
            transition: border-color 0.2s ease;
        }

        #archivos-container .form-control:hover {
            border-color: #007bff;
        }

        #archivos-container .form-control:focus {
            border-color: #007bff;
            border-style: solid;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        @media (max-width: 768px) {
            .kanban-column {
                flex: 0 0 280px;
            }

            .sidebar-right {
                width: 100%;
                right: -100%;
            }

            .archivo-item .d-flex {
                flex-direction: column;
                gap: 10px;
            }

            .archivo-item .btn-danger {
                align-self: flex-end;
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeKanban();
        });

        document.addEventListener('livewire:navigated', function() {
            initializeKanban();
        });

        function initializeKanban() {
            const columns = document.querySelectorAll('.kanban-cards');

            columns.forEach(column => {
                new Sortable(column, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    handle: '.kanban-card',
                    onEnd: function(evt) {
                        const tareaId = evt.item.dataset.tareaId;
                        const columnaId = evt.to.dataset.columnaId;
                        const nuevoOrden = evt.newIndex;

                        Livewire.dispatch('tareaMovida', {
                            tareaId: tareaId,
                            columnaId: columnaId,
                            nuevoOrden: nuevoOrden
                        });
                    }
                });
            });
        }

        window.addEventListener('swal:success', event => {
            Swal.fire({
                icon: 'success',
                title: event.detail[0].title,
                text: event.detail[0].text,
                showConfirmButton: false,
                timer: 2000
            });
        });

        window.addEventListener('swal:error', event => {
            Swal.fire({
                icon: 'error',
                title: event.detail[0].title,
                text: event.detail[0].text
            });
        });

        window.addEventListener('swal:confirm', event => {
            Swal.fire({
                icon: 'warning',
                title: event.detail[0].title,
                text: event.detail[0].text,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'S�, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(event.detail[0].method, event.detail[0].params);
                }
            });
        });

        Fancybox.bind('[data-fancybox]', {
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: [],
                    right: ["slideshow", "download", "thumbs", "close"],
                },
            },
        });
    </script>
@stop