@php $isHome = request()->routeIs('home'); @endphp

<header class="navbar{{ $isHome ? ' navbar--home' : '' }}" id="mainNav">

    <div class="nav-container">

        <!-- LOGO -->
        <div class="logo">
            <a href="{{ route('home') }}">
                <img src="{{ asset('storage/images/logos/logo.png') }}" alt="CONAR">
            </a>
        </div>

        <!-- HAMBURGER (solo visible en mobile) -->
        <button class="hamburger" id="hamburgerBtn" aria-label="Abrir menú" aria-expanded="false">
            <span class="hamburger__line"></span>
            <span class="hamburger__line"></span>
            <span class="hamburger__line"></span>
        </button>

        <!-- OVERLAY (fondo oscuro en mobile) -->
        <div class="nav-overlay" id="navOverlay"></div>

        <!-- NAV + SEARCH -->
        <nav class="nav-links" id="navLinks">

            <button class="nav-close" id="navClose" aria-label="Cerrar menú">✕</button>

            <a href="{{ route('nosotros') }}"
               class="{{ request()->routeIs('nosotros') ? 'active' : '' }}">Nosotros</a>

            <a href="{{ route('products.index') }}"
               class="{{ request()->routeIs('products.*') ? 'active' : '' }}">Productos</a>

            <a href="{{ route('industries.index') }}"
               class="{{ request()->routeIs('industries.*') ? 'active' : '' }}">Industrias</a>

            <a href="{{ route('posts.index') }}"
               class="{{ request()->routeIs('posts.*') ? 'active' : '' }}">Novedades</a>

            <a href="{{ route('contact.index') }}"
               class="{{ request()->routeIs('contact.*') ? 'active' : '' }}">Contacto</a>

            <a href="{{ route('search') }}" class="nav-search-icon" aria-label="Buscar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21L16.66 16.66M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="{{ $isHome ? '#101010' : '#136CBA' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <!-- Presupuesto dentro del nav en mobile -->
            <a href="{{ route('presupuesto') }}" class="btn-presupuesto btn-presupuesto--mobile">
                Presupuesto
            </a>

        </nav>

        <!-- BOTÓN PRESUPUESTO (desktop only) -->
        <a href="{{ route('presupuesto') }}" class="btn-presupuesto btn-presupuesto--desktop">
            Presupuesto
        </a>

    </div>

</header>

