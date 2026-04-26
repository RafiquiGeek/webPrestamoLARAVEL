/**
 * Admin Layout JavaScript Optimizado
 * Extraído del layout para mejorar rendimiento
 */

class AdminLayout {
    constructor() {
        this.appState = {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            openSubmenus: JSON.parse(localStorage.getItem('openSubmenus') || '[]'),
            currentTheme: localStorage.getItem('theme') || 'light',
            currentColor: localStorage.getItem('colorScheme') || 'blue'
        };

        this.attendanceState = {
            currentStatus: null,
            lastAction: null,
            lastActionTime: null,
            isBreakActive: false,
            dayCompleted: false,
            nonWorkingDayData: null
        };

        this.locationVerified = window.ubicacionVerificada || false;

        this.init();
    }

    init() {
        this.initializeElements();
        this.setupEventListeners();
        this.restoreState();
        this.checkLocationVerification();
    }

    initializeElements() {
        this.elements = {
            // Layout elements
            sidebar: document.querySelector('.main-sidebar'),
            sidebarToggle: document.getElementById('sidebarToggle'),
            sidebarMenu: document.getElementById('sidebarMenu'),
            contentWrapper: document.getElementById('contentWrapper'),

            // Theme elements
            themeToggle: document.getElementById('themeToggle'),
            colorOptions: document.querySelectorAll('.color-option'),

            // User menu
            userMenu: document.querySelector('.user-menu'),

            // Attendance elements
            attendanceButton: document.getElementById('attendanceFloatButton'),
            attendanceMenu: document.getElementById('attendanceMenu'),
            attendanceContainer: document.getElementById('attendanceFloatContainer'),

            // Location elements
            locationVerifying: document.getElementById('locationVerifying'),
            requestLocationBtn: document.getElementById('requestLocationBtn')
        };
    }

    setupEventListeners() {
        // Sidebar toggle
        if (this.elements.sidebarToggle) {
            this.elements.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Menu items
        if (this.elements.sidebarMenu) {
            this.elements.sidebarMenu.addEventListener('click', (e) => this.handleMenuClick(e));
        }

        // Theme toggle
        if (this.elements.themeToggle) {
            this.elements.themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Color options
        this.elements.colorOptions.forEach(option => {
            option.addEventListener('click', (e) => this.changeColorScheme(e.target.dataset.color));
        });

        // Attendance
        if (this.elements.attendanceButton) {
            this.elements.attendanceButton.addEventListener('click', () => this.toggleAttendanceMenu());
        }

        // Location verification
        if (this.elements.requestLocationBtn) {
            this.elements.requestLocationBtn.addEventListener('click', () => this.requestLocation());
        }

        // Click outside to close menus
        document.addEventListener('click', (e) => this.handleOutsideClick(e));

        // Window resize
        window.addEventListener('resize', () => this.handleResize());
    }

    toggleSidebar() {
        this.appState.sidebarCollapsed = !this.appState.sidebarCollapsed;
        this.elements.sidebar?.classList.toggle('collapsed', this.appState.sidebarCollapsed);
        localStorage.setItem('sidebarCollapsed', this.appState.sidebarCollapsed);

        // Trigger resize event for charts/tables
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 350);
    }

    handleMenuClick(e) {
        const menuLink = e.target.closest('.menu-link');
        if (!menuLink) return;

        const submenuId = menuLink.getAttribute('data-submenu');
        if (submenuId) {
            e.preventDefault();
            this.toggleSubmenu(submenuId, menuLink.parentElement);
        }
    }

    toggleSubmenu(submenuId, menuItem) {
        const isOpen = menuItem.classList.contains('open');

        if (isOpen) {
            menuItem.classList.remove('open');
            this.appState.openSubmenus = this.appState.openSubmenus.filter(id => id !== submenuId);
        } else {
            // Close other submenus if not in collapsed mode
            if (!this.appState.sidebarCollapsed) {
                document.querySelectorAll('.menu-item.has-submenu.open').forEach(openItem => {
                    if (openItem !== menuItem) {
                        openItem.classList.remove('open');
                    }
                });
                this.appState.openSubmenus = [submenuId];
            } else {
                this.appState.openSubmenus.push(submenuId);
            }

            menuItem.classList.add('open');
        }

        localStorage.setItem('openSubmenus', JSON.stringify(this.appState.openSubmenus));
    }

    toggleTheme() {
        this.appState.currentTheme = this.appState.currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.appState.currentTheme);
        this.elements.themeToggle?.classList.toggle('dark', this.appState.currentTheme === 'dark');
        localStorage.setItem('theme', this.appState.currentTheme);
    }

    changeColorScheme(color) {
        if (!color) return;

        this.appState.currentColor = color;

        // Update CSS variables
        const colorMap = {
            blue: '#3049a7',
            purple: '#7c3aed',
            green: '#059669',
            orange: '#ea580c',
            pink: '#db2777',
            teal: '#0891b2'
        };

        const primaryColor = colorMap[color];
        if (primaryColor) {
            document.documentElement.style.setProperty('--primary', primaryColor);

            // Update active color option
            this.elements.colorOptions.forEach(option => {
                option.classList.toggle('active', option.dataset.color === color);
            });

            localStorage.setItem('colorScheme', color);
        }
    }

    restoreState() {
        // Restore sidebar state
        this.elements.sidebar?.classList.toggle('collapsed', this.appState.sidebarCollapsed);

        // Restore theme
        document.documentElement.setAttribute('data-theme', this.appState.currentTheme);
        this.elements.themeToggle?.classList.toggle('dark', this.appState.currentTheme === 'dark');

        // Restore color scheme
        this.changeColorScheme(this.appState.currentColor);

        // Restore submenus
        this.appState.openSubmenus.forEach(submenuId => {
            const menuItem = document.querySelector(`[data-submenu="${submenuId}"]`)?.parentElement;
            if (menuItem) {
                menuItem.classList.add('open');
            }
        });
    }

    handleOutsideClick(e) {
        // Close attendance menu if clicking outside
        if (this.elements.attendanceContainer &&
            !this.elements.attendanceContainer.contains(e.target)) {
            this.closeAttendanceMenu();
        }
    }

    handleResize() {
        const isMobile = window.innerWidth <= 768;

        if (isMobile && !this.appState.sidebarCollapsed) {
            this.toggleSidebar();
        }
    }

    // Attendance functionality
    toggleAttendanceMenu() {
        if (!this.elements.attendanceMenu) return;

        const isOpen = this.elements.attendanceMenu.classList.contains('show');
        if (isOpen) {
            this.closeAttendanceMenu();
        } else {
            this.openAttendanceMenu();
        }
    }

    openAttendanceMenu() {
        this.elements.attendanceMenu?.classList.add('show');
        this.elements.attendanceContainer?.classList.add('menu-open');
    }

    closeAttendanceMenu() {
        this.elements.attendanceMenu?.classList.remove('show');
        this.elements.attendanceContainer?.classList.remove('menu-open');
    }

    // Location verification (simplified)
    checkLocationVerification() {
        try {
            const sessionVerified = sessionStorage.getItem('location_session_verified');
            const cookieVerified = document.cookie.includes('ubicacion_ok=1');

            if (sessionVerified === 'true' || cookieVerified) {
                this.locationVerified = true;
                window.ubicacionVerificada = true;

                if (this.elements.locationVerifying) {
                    this.elements.locationVerifying.style.display = 'none';
                }

                setTimeout(() => {
                    if (typeof inicializarSistema === 'function') {
                        inicializarSistema();
                    }
                }, 100);
            } else if (this.elements.locationVerifying) {
                this.elements.locationVerifying.style.display = 'flex';
            }
        } catch (error) {
            console.error('Error checking location verification:', error);
        }
    }

    requestLocation() {
        if (!navigator.geolocation) {
            this.showLocationError('Geolocalización no soportada');
            return;
        }

        this.updateLocationStatus('Solicitando permisos...', 20);

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            (position) => this.onLocationSuccess(position),
            (error) => this.onLocationError(error),
            options
        );
    }

    onLocationSuccess(position) {
        const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            timestamp: new Date().getTime(),
            accuracy: position.coords.accuracy
        };

        localStorage.setItem('user_location', JSON.stringify(locationData));
        sessionStorage.setItem('location_session_verified', 'true');

        // Set cookie
        const expiry = new Date();
        expiry.setHours(expiry.getHours() + 24);
        document.cookie = `ubicacion_ok=1; expires=${expiry.toUTCString()}; path=/`;

        this.updateLocationStatus('¡Ubicación verificada!', 100);

        setTimeout(() => {
            if (this.elements.locationVerifying) {
                this.elements.locationVerifying.style.display = 'none';
            }
            this.locationVerified = true;
            window.ubicacionVerificada = true;
        }, 1000);
    }

    onLocationError(error) {
        let message = 'Error desconocido';

        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = 'Permisos de ubicación denegados';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Ubicación no disponible';
                break;
            case error.TIMEOUT:
                message = 'Tiempo de espera agotado';
                break;
        }

        this.showLocationError(message);
    }

    updateLocationStatus(text, progress) {
        const statusElement = document.getElementById('statusText');
        const progressElement = document.getElementById('progressFill');
        const statusContainer = document.getElementById('locationStatus');

        if (statusElement) statusElement.textContent = text;
        if (progressElement) progressElement.style.width = `${progress}%`;
        if (statusContainer) statusContainer.style.display = 'block';
    }

    showLocationError(message) {
        const Swal = window.Swal;
        if (!Swal) {
            alert(message);
            return;
        }

        Swal.fire({
            title: 'Error de Ubicación',
            text: message,
            icon: 'error',
            confirmButtonText: 'Reintentar',
            showCancelButton: true,
            cancelButtonText: 'Cerrar Sesión'
        }).then((result) => {
            if (result.isConfirmed) {
                this.requestLocation();
            } else {
                this.performLogout();
            }
        });
    }

    performLogout() {
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.submit();
        } else {
            window.location.href = '/login';
        }
    }

    // Utility methods
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fade-in`;
        notification.innerHTML = `<i class="fas fa-info-circle mr-2"></i> ${message}`;

        const container = this.elements.contentWrapper;
        if (container) {
            container.insertBefore(notification, container.firstChild);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }

    // Advanced actions
    cleanCache() {
        try {
            localStorage.clear();
            sessionStorage.clear();

            // Clear cookies
            document.cookie.split(";").forEach(function(c) {
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
            });

            this.showNotification('Cache limpiado exitosamente', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } catch (error) {
            console.error('Error cleaning cache:', error);
            this.showNotification('Error al limpiar cache', 'error');
        }
    }

    forceReload() {
        window.location.reload(true);
    }

    // Initialize fade-in animations
    initializeAnimations() {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in-visible');
                    }
                });
            },
            { threshold: 0.1 }
        );

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
    }
}

// Global utility functions
window.performLogout = function() {
    if (window.adminLayout) {
        window.adminLayout.performLogout();
    } else {
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.submit();
        } else {
            window.location.href = '/login';
        }
    }
};

window.limpiezaNuclear = function() {
    if (window.adminLayout) {
        window.adminLayout.cleanCache();
    }
};

// CSRF Token setup
window.Laravel = window.Laravel || {};
document.addEventListener('DOMContentLoaded', function() {
    // Set CSRF token
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.Laravel.csrfToken = token.getAttribute('content');
    }

    // Initialize admin layout
    window.adminLayout = new AdminLayout();

    // Initialize animations
    window.adminLayout.initializeAnimations();

    console.log('Admin layout initialized successfully');
});

// Handle Livewire events
document.addEventListener('livewire:navigated', () => {
    if (window.adminLayout) {
        window.adminLayout.initializeAnimations();
    }
});

document.addEventListener('livewire:init', () => {
    console.log('Livewire initialized with optimized layout');
});

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminLayout;
}