<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Grupo Santiago - Préstamos para Jóvenes</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <style>
    :root {
      --primary-color: #00C897; /* Verde juvenil */
      --secondary-color: #2D3748; /* Gris oscuro */
      --accent-color: #FF6F61; /* Coral para CTA */
      --light-bg: #F9FAFB; /* Fondo claro */
      --white: #FFFFFF;
      --shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: var(--light-bg); color: var(--secondary-color); line-height: 1.5; }
    a { text-decoration: none; color: inherit; }

    /* Header */
    header {
      background: var(--white);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
      box-shadow: var(--shadow);
    }
    .header-container {
      max-width: 1300px;
      margin: 0 auto;
      padding: 1rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .logo img { width: 120px; }
    nav ul { display: flex; gap: 2rem; list-style: none; }
    nav a { font-weight: 600; font-size: 1rem; color: var(--secondary-color);  }
    nav a:hover { color: var(--primary-color); }
    .header-buttons { display: flex; gap: 1rem; }
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      font-weight: 600;
      
    }
    .btn-primary { background: var(--primary-color); color: var(--white); }
    .btn-primary:hover { background: #00A77A; }
    .btn-secondary { background: transparent; color: var(--primary-color); border: 2px solid var(--primary-color); }
    .btn-secondary:hover { background: var(--primary-color); color: var(--white); }
    .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; }

    @media (max-width: 768px) {
      .menu-toggle { display: block; }
      nav ul {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: var(--white);
        width: 100%;
        padding: 1rem;
        flex-direction: column;
        text-align: center;
        box-shadow: var(--shadow);
      }
      nav ul.show { display: flex; }
      .header-buttons { display: none; }
    }

    /* Hero */
    .hero {
      min-height: 100vh;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: var(--white);
      display: flex;
      align-items: center;
      padding: 6rem 2rem 4rem;
      margin-top: 70px;
    }
    .hero-container {
      max-width: 1300px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 3rem;
    }
    .hero-text h1 { font-size: 3.5rem; font-weight: 700; line-height: 1.1; margin-bottom: 1rem; }
    .hero-text p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; }
    .hero-btn { background: var(--accent-color); color: var(--white); padding: 1rem 2rem; border-radius: 50px; font-size: 1.1rem; font-weight: 600;  }
    .hero-btn:hover { transform: scale(1.05); background: #FF877A; }
    .hero-image img { max-width: 500px; width: 100%; border-radius: 20px; box-shadow: var(--shadow); }

    @media (max-width: 768px) {
      .hero-container { flex-direction: column; text-align: center; }
      .hero-text h1 { font-size: 2.5rem; }
      .hero-image img { max-width: 300px; }
    }

    /* Servicios */
    .services { padding: 4rem 2rem; background: var(--white); }
    .services-container { max-width: 1300px; margin: 0 auto; text-align: center; }
    .services-container h2 { font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 2rem; }
    .service-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
    .service-card { padding: 2rem; background: var(--light-bg); border-radius: 15px;  }
    .service-card:hover { transform: translateY(-10px); box-shadow: var(--shadow); }
    .service-card i { font-size: 2.5rem; color: var(--primary-color); margin-bottom: 1rem; }
    .service-card h3 { font-size: 1.5rem; font-weight: 600; color: var(--secondary-color); }
    .service-card p { font-size: 1rem; color: var(--secondary-color); margin-bottom: 1rem; }
    .service-card .btn { font-size: 0.9rem; padding: 0.5rem 1.5rem; }

    /* Testimonios */
    .testimonials { padding: 4rem 2rem; background: var(--light-bg); }
    .testimonials-container { max-width: 1300px; margin: 0 auto; text-align: center; }
    .testimonials-container h2 { font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 2rem; }
    .testimonial-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; }
    .testimonial-card { padding: 2rem; background: var(--white); border-radius: 15px; box-shadow: var(--shadow);  }
    .testimonial-card:hover { transform: translateY(-5px); }
    .testimonial-card img { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 1rem; }
    .testimonial-card p { font-size: 1rem; color: var(--secondary-color); margin-bottom: 1rem; }
    .testimonial-card h4 { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); }

    /* Footer */
    .footer { background: var(--secondary-color); color: var(--white); padding: 3rem 2rem; }
    .footer-container { max-width: 1300px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; }
    .footer-section h4 { font-size: 1.5rem; margin-bottom: 1rem; }
    .footer-section p, .footer-section ul li { font-size: 0.95rem; }
    .footer-section ul { list-style: none; padding: 0; }
    .footer-section a:hover { color: var(--primary-color); }
    .social-icons a { font-size: 1.5rem; margin-right: 1rem;  }
    .social-icons a:hover { color: var(--primary-color); }
    .footer-bottom { text-align: center; margin-top: 2rem; font-size: 0.85rem; opacity: 0.8; }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="header-container">
      <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="Grupo Santiago"/>
      </div>
      <nav>
        <button class="menu-toggle"><i class="fas fa-bars"></i></button>
        <ul>
          <li><a href="#servicios">Servicios</a></li>
          <li><a href="#testimonios">Testimonios</a></li>
          <li><a href="#contacto">Contacto</a></li>
        </ul>
      </nav>
      <div class="header-buttons">
        <a href="{{ route('login') }}" class="btn btn-primary">Iniciar Sesión</a>
        <a href="#solicitar" class="btn btn-secondary">Solicitar Préstamo</a>
      </div>
    </div>
  </header>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-container">
      <div class="hero-text">
        <h1>Préstamos que Sí Entiendes</h1>
        <p>Rápido, fácil y sin complicaciones. Consigue el dinero que necesitas hoy mismo.</p>
        <a href="#solicitar" class="hero-btn">Solicitar Ahora</a>
      </div>
      <div class="hero-image">
        <img src="{{ asset('images/hero-young.png') }}" alt="Préstamos para jóvenes"/>
      </div>
    </div>
  </section>

  <!-- Servicios -->
  <section id="servicios" class="services">
    <div class="services-container">
      <h2>Nuestros Servicios</h2>
      <div class="service-cards">
        <div class="service-card">
          <i class="fas fa-user"></i>
          <h3>Préstamos Personales</h3>
          <p>Financia tus proyectos personales con flexibilidad y sin estrés.</p>
          <a href="#solicitar" class="btn btn-secondary">Más Info</a>
        </div>
        <div class="service-card">
          <i class="fas fa-home"></i>
          <h3>Préstamos Hipotecarios</h3>
          <p>Compra tu casa soñada con tasas que no te asustan.</p>
          <a href="#solicitar" class="btn btn-secondary">Más Info</a>
        </div>
        <div class="service-card">
          <i class="fas fa-briefcase"></i>
          <h3>Préstamos para Negocios</h3>
          <p>Haz crecer tu emprendimiento con el capital que necesitas.</p>
          <a href="#solicitar" class="btn btn-secondary">Más Info</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonios -->
  <section id="testimonios" class="testimonials">
    <div class="testimonials-container">
      <h2>Qué Dicen de Nosotros</h2>
      <div class="testimonial-cards">
        <div class="testimonial-card">
          <img src="{{ asset('images/client1.jpg') }}" alt="María López"/>
          <p>"Conseguí un préstamo súper rápido para mi emprendimiento. ¡100% recomendado!"</p>
          <h4>María López</h4>
        </div>
        <div class="testimonial-card">
          <img src="{{ asset('images/client2.jpg') }}" alt="Carlos García"/>
          <p>"El proceso fue fácil y las tasas son geniales. Volvería sin duda."</p>
          <h4>Carlos García</h4>
        </div>
        <div class="testimonial-card">
          <img src="{{ asset('images/client3.jpg') }}" alt="Laura Fernández"/>
          <p>"Me ayudaron a comprar mi primera casa. ¡Equipo top!"</p>
          <h4>Laura Fernández</h4>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="contacto" class="footer">
    <div class="footer-container">
      <div class="footer-section">
        <h4>Grupo Santiago</h4>
        <p>Tu aliado financiero para hacer realidad tus planes.</p>
      </div>
      <div class="footer-section">
        <h4>Enlaces Rápidos</h4>
        <ul>
          <li><a href="#servicios">Servicios</a></li>
          <li><a href="#testimonios">Testimonios</a></li>
          <li><a href="#contacto">Contacto</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Contáctanos</h4>
        <p>Dirección: Calle Falsa 123, Lima, Perú</p>
        <p>Teléfono: +51 123 456 789</p>
        <p>Email: contacto@gruposantiago.com.pe</p>
      </div>
      <div class="footer-section">
        <h4>Síguenos</h4>
        <div class="social-icons">
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-tiktok"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© {{ date('Y') }} Grupo Santiago. Todos los derechos reservados.</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
  <script>
    $(document).ready(function () {
      // Menú hamburguesa
      $(".menu-toggle").click(function () {
        $("nav ul").toggleClass("show");
      });
    });
  </script>
</body>
</html>