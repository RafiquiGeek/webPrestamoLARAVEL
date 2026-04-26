<x-guest-layout>
    <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF6F61] selection:text-white">

        <!-- Header/Navbar -->
        <header class="fixed w-full top-0 z-50 bg-white/90 backdrop-blur-md shadow-sm transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ url('/') }}" class="flex items-center gap-2">
                            @if(file_exists(public_path('images/logo.png')))
                                <img class="h-10 w-auto" src="{{ asset('images/logo.png') }}" alt="Grupo Santiago">
                            @else
                                <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-[#00C897] to-[#2D3748]">Grupo Santiago</span>
                            @endif
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <nav class="hidden md:flex space-x-8">
                        <a href="#servicios" class="text-gray-600 hover:text-[#00C897] font-medium transition-colors duration-200">Servicios</a>
                        <a href="#testimonios" class="text-gray-600 hover:text-[#00C897] font-medium transition-colors duration-200">Testimonios</a>
                        <a href="#contacto" class="text-gray-600 hover:text-[#00C897] font-medium transition-colors duration-200">Contacto</a>
                    </nav>

                    <!-- CTA Buttons -->
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="{{ route('login') }}" class="text-[#00C897] hover:text-[#00A77A] font-semibold transition-colors duration-200">
                            Iniciar Sesión
                        </a>
                        <a href="#solicitar" class="px-6 py-2.5 bg-[#00C897] text-white rounded-full font-semibold shadow-lg shadow-[#00C897]/30 hover:bg-[#00A77A] hover:shadow-[#00C897]/50 hover:-translate-y-0.5 transition-all duration-300">
                            Solicitar Préstamo
                        </a>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button id="mobile-menu-button" class="text-gray-600 hover:text-[#00C897] focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full left-0">
                <div class="px-4 pt-2 pb-6 space-y-2 shadow-lg">
                    <a href="#servicios" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-[#00C897] hover:bg-gray-50 rounded-md">Servicios</a>
                    <a href="#testimonios" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-[#00C897] hover:bg-gray-50 rounded-md">Testimonios</a>
                    <a href="#contacto" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-[#00C897] hover:bg-gray-50 rounded-md">Contacto</a>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col space-y-3">
                        <a href="{{ route('login') }}" class="block text-center text-gray-600 font-medium">Iniciar Sesión</a>
                        <a href="#solicitar" class="block text-center px-4 py-3 bg-[#00C897] text-white rounded-lg font-bold">Solicitar Préstamo</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative w-full pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
            <!-- Background Gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-[#00C897] via-[#1a4a40] to-[#2D3748] transform -skew-y-3 origin-top-left scale-110 z-0"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-white">
                <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                    <!-- Text Content -->
                    <div class="flex-1 text-center lg:text-left">
                        <span class="inline-block py-1 px-3 rounded-full bg-white/20 backdrop-blur-sm text-sm font-semibold tracking-wide mb-6 border border-white/30">
                            🚀 Financia tus sueños hoy
                        </span>
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight mb-6">
                            Préstamos que <br class="hidden lg:block">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#a0f0d8] to-white">Sí Entiendes</span>
                        </h1>
                        <p class="text-lg md:text-xl text-gray-100 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-light">
                            Rápido, fácil y sin complicaciones. Olvídate de la burocracia y consigue el capital que necesitas para impulsar tu futuro en cuestión de minutos.
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a href="#solicitar" class="w-full sm:w-auto px-8 py-4 bg-[#FF6F61] text-white rounded-full font-bold text-lg shadow-xl shadow-[#FF6F61]/40 hover:bg-[#e65b4e] hover:scale-105 hover:shadow-2xl transition-all duration-300">
                                Solicitar Ahora
                            </a>
                            <a href="#como-funciona" class="w-full sm:w-auto px-8 py-4 bg-transparent border-2 border-white/30 text-white rounded-full font-bold text-lg hover:bg-white/10 hover:border-white transition-all duration-300 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Cómo funciona
                            </a>
                        </div>
                        <div class="mt-10 flex items-center justify-center lg:justify-start gap-6 text-sm font-medium text-gray-200/80">
                            <div class="flex items-center gap-1">
                                <svg class="w-5 h-5 text-[#00C897]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Sin aval
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-5 h-5 text-[#00C897]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                100% Online
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="w-5 h-5 text-[#00C897]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Respuesta inmediata
                            </div>
                        </div>
                    </div>

                    <!-- Image/Illustration -->
                    <div class="flex-1 relative w-full max-w-lg lg:max-w-none">
                        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 bg-[#FF6F61] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
                        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 bg-[#00C897] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
                        
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl transform hover:rotate-1 hover:scale-[1.01] transition-all duration-500 ring-8 ring-white/10">
                            <img src="{{ asset('images/hero-young.png') }}" class="w-full h-auto object-cover" alt="Jóvenes disfrutando libertad financiera">
                            
                            <!-- Floating Card -->
                            <div class="absolute bottom-6 left-6 right-6 bg-white/95 backdrop-blur-md p-4 rounded-xl shadow-lg flex items-center gap-4">
                                <div class="bg-green-100 p-3 rounded-full text-green-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 font-medium">Préstamo Aprobado</p>
                                    <p class="text-lg font-bold text-gray-900">S/ 5,000.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Wave Divider -->
            <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-10">
                <svg class="relative block w-full h-[60px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="fill-white"></path>
                </svg>
            </div>
        </section>

        <!-- Services Section -->
        <section id="servicios" class="py-24 bg-white w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-[#00C897] font-bold text-lg uppercase tracking-wider mb-2">Nuestros Servicios</h2>
                    <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Soluciones diseñadas para ti</h3>
                    <p class="text-gray-500 text-lg">Elegí la opción que mejor se adapte a tus necesidades y comenzá a cumplir tus metas hoy.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 ">
                    <!-- Card 1 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-blue-50 rounded-full group-hover:bg-[#00C897]/10 transition-colors"></div>
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#00C897] group-hover:text-white transition-all duration-300">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Préstamos Personales</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Financia tus proyectos personales con flexibilidad. Tasas competitivas y plazos a tu medida.
                        </p>
                        <a href="#solicitar" class="inline-flex items-center text-[#00C897] font-semibold hover:tracking-wide transition-all">
                            Solicitar ahora <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </a>
                    </div>

                    <!-- Card 2 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 relative overflow-hidden">
                         <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-purple-50 rounded-full group-hover:bg-[#00C897]/10 transition-colors"></div>
                        <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#00C897] group-hover:text-white transition-all duration-300">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Préstamos Hipotecarios</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            El hogar de tus sueños está más cerca. Te ayudamos a dar el gran paso con total confianza.
                        </p>
                         <a href="#solicitar" class="inline-flex items-center text-[#00C897] font-semibold hover:tracking-wide transition-all">
                            Solicitar ahora <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </a>
                    </div>

                    <!-- Card 3 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 relative overflow-hidden">
                         <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-orange-50 rounded-full group-hover:bg-[#00C897]/10 transition-colors"></div>
                        <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-[#00C897] group-hover:text-white transition-all duration-300">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Préstamos para Negocios</h3>
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            Impulsa tu emprendimiento. Capital de trabajo y expansión con el respaldo que necesitas.
                        </p>
                         <a href="#solicitar" class="inline-flex items-center text-[#00C897] font-semibold hover:tracking-wide transition-all">
                            Solicitar ahora <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 bg-[#2D3748] w-full text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x divide-gray-700/50">
                    <div class="p-4">
                        <div class="text-4xl font-bold text-[#00C897] mb-2 counter">5k+</div>
                        <p class="text-gray-300">Clientes Felices</p>
                    </div>
                    <div class="p-4">
                        <div class="text-4xl font-bold text-[#00C897] mb-2 counter">S/ 2M+</div>
                        <p class="text-gray-300">Prestados</p>
                    </div>
                    <div class="p-4">
                        <div class="text-4xl font-bold text-[#00C897] mb-2 counter">98%</div>
                        <p class="text-gray-300">Tasa de Aprobación</p>
                    </div>
                     <div class="p-4 border-none">
                        <div class="text-4xl font-bold text-[#00C897] mb-2 counter">24/7</div>
                        <p class="text-gray-300">Soporte Activo</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section id="testimonios" class="py-24 bg-gray-50 w-full relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Lo que dicen nuestros clientes</h2>
                    <p class="text-gray-500 text-lg max-w-2xl mx-auto">La confianza de nuestros usuarios es nuestra mejor carta de presentación.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Testimonial 1 -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center gap-4 mb-6">
                            <img src="{{ asset('images/client1.jpg') }}" alt="User" class="w-14 h-14 rounded-full object-cover border-2 border-[#00C897]">
                            <div>
                                <h4 class="font-bold text-gray-900">María López</h4>
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 italic">"Increíble la rapidez. En menos de 24 horas ya tenía el dinero en mi cuenta. Gracias a Grupo Santiago pude comprar la maquinaria que me faltaba."</p>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300">
                         <div class="flex items-center gap-4 mb-6">
                            <img src="{{ asset('images/client2.jpg') }}" alt="User" class="w-14 h-14 rounded-full object-cover border-2 border-[#00C897]">
                            <div>
                                <h4 class="font-bold text-gray-900">Carlos García</h4>
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 italic">"Las tasas son justas y el trato es muy amable. Me explicaron todo con detalle y no hubo letras chiquitas. Muy recomendado."</p>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300">
                         <div class="flex items-center gap-4 mb-6">
                            <img src="{{ asset('images/client3.jpg') }}" alt="User" class="w-14 h-14 rounded-full object-cover border-2 border-[#00C897]">
                            <div>
                                <h4 class="font-bold text-gray-900">Laura Fernández</h4>
                                <div class="flex text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 italic">"El proceso fue 100% digital, lo cual valoro mucho. Atención de primera y gran eficiencia. Definitivamente volveré a confiar en ellos."</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 w-full bg-gradient-to-r from-[#00C897] to-[#00A77A] text-white overflow-hidden relative">
            <div class="absolute inset-0 bg-[url('/img/pattern.png')] opacity-10"></div>
            <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
                <h2 class="text-4xl font-bold mb-6">¿Listo para empezar?</h2>
                <p class="text-xl mb-8 text-green-50">No dejes pasar más tiempo. Simula tu préstamo ahora y descubre lo fácil que es.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="#solicitar" class="px-8 py-4 bg-white text-[#00C897] rounded-full font-bold text-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        Solicitar mi préstamo
                    </a>
                    <a href="#contacto" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-full font-bold text-lg hover:bg-white/10 transition-all duration-300">
                        Contáctanos
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contacto" class="bg-[#2D3748] text-white pt-16 pb-8 border-t border-gray-700 w-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                    <div>
                        <h4 class="text-2xl font-bold mb-6 text-[#00C897]">Grupo Santiago</h4>
                        <p class="text-gray-400 leading-relaxed mb-6">
                            Comprometidos con tu crecimiento financiero. Ofrecemos soluciones ágiles y transparentes para que alcances tus metas.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-[#00C897] hover:text-white transition-all"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-[#00C897] hover:text-white transition-all"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-[#00C897] hover:text-white transition-all"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-bold mb-6">Enlaces Rápidos</h4>
                        <ul class="space-y-3 text-gray-400">
                            <li><a href="#servicios" class="hover:text-[#00C897] transition-colors">Nuestros Servicios</a></li>
                            <li><a href="#testimonios" class="hover:text-[#00C897] transition-colors">Testimonios</a></li>
                            <li><a href="#solicitar" class="hover:text-[#00C897] transition-colors">Solicitar Préstamo</a></li>
                            <li><a href="{{ route('login') }}" class="hover:text-[#00C897] transition-colors">Área de Clientes</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-lg font-bold mb-6">Contacto</h4>
                        <ul class="space-y-4 text-gray-400">
                            <li class="flex items-start gap-3">
                                <i class="fas fa-map-marker-alt mt-1 text-[#00C897]"></i>
                                <span>Calle Falsa 123, Of. 401<br>San Isidro, Lima, Perú</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fas fa-phone text-[#00C897]"></i>
                                <span>+51 123 456 789</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <i class="fas fa-envelope text-[#00C897]"></i>
                                <span>contacto@gruposantiago.com.pe</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-lg font-bold mb-6">Horario de Atención</h4>
                         <ul class="space-y-3 text-gray-400">
                            <li class="flex justify-between">
                                <span>Lunes - Viernes:</span>
                                <span>9:00 - 18:00</span>
                            </li>
                            <li class="flex justify-between">
                                <span>Sábados:</span>
                                <span>9:00 - 13:00</span>
                            </li>
                            <li class="flex justify-between">
                                <span>Domingos:</span>
                                <span class="text-[#FF6F61]">Cerrado</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-gray-700 pt-8 text-center text-gray-500 text-sm">
                    <p>&copy; {{ date('Y') }} Grupo Santiago. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Smooth Scroll & Mobile Menu Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-button');
            const menu = document.getElementById('mobile-menu');

            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
    <style>
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
    </style>
</x-guest-layout>
