<!-- resources/views/components/sidebar.blade.php -->

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Grupo Santiago" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Grupo Santiago</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @foreach(config('adminlte.menu') as $item)
                    @if(isset($item['header']))
                        <li class="nav-header">{{ $item['header'] }}</li>
                    @else
                        @if(isset($item['submenu']))
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="{{ $item['icon'] }} nav-icon"></i>
                                    <p>
                                        {{ $item['text'] }}
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @foreach($item['submenu'] as $submenu)
                                        <li class="nav-item">
                                            @php
                                                $submenuHref = '#';
                                                if (isset($submenu['route'])) {
                                                    $submenuHref = route($submenu['route']);
                                                } elseif (isset($submenu['url'])) {
                                                    $submenuHref = url($submenu['url']);
                                                }
                                            @endphp
                                            <a href="{{ $submenuHref }}" class="nav-link">
                                                <i class="{{ $submenu['icon'] ?? 'far fa-circle' }} nav-icon"></i>
                                                <p>{{ $submenu['text'] ?? 'Sin título' }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                @php
                                    $href = '#';
                                    if (isset($item['route'])) {
                                        $href = route($item['route']);
                                    } elseif (isset($item['url'])) {
                                        $href = url($item['url']);
                                    }
                                @endphp
                                <a href="{{ $href }}" class="nav-link">
                                    <i class="{{ $item['icon'] ?? 'fas fa-circle' }} nav-icon"></i>
                                    <p>{{ $item['text'] ?? 'Sin título' }}</p>
                                </a>
                            </li>
                        @endif
                    @endif
                @endforeach
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
