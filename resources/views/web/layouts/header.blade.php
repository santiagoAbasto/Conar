@php
    $isHome = request()->routeIs('home');
    $languages = [
        'es' => ['label' => 'Español', 'short' => 'ES'],
        'pt' => ['label' => 'Português', 'short' => 'PT'],
        'en' => ['label' => 'English', 'short' => 'EN'],
    ];
    $currentLocale = app()->getLocale();
@endphp

<header class="navbar{{ $isHome ? ' navbar--home' : '' }}" id="mainNav">

    <div class="nav-container">

        <!-- LOGO -->
        <div class="logo">
            <a href="{{ route('home') }}">
                <img src="{{ asset('storage/images/logos/logo.png') }}" alt="CONAR">
            </a>
        </div>

        <!-- OVERLAY (fondo oscuro en mobile) -->
        <div class="nav-overlay" id="navOverlay"></div>

        <!-- NAV + SEARCH -->
        <nav class="nav-links" id="navLinks">

            <button class="nav-close" id="navClose" aria-label="Cerrar menú">✕</button>

            <a href="{{ route('home') }}" class="mobile-drawer-logo" aria-label="CONAR">
                <img src="{{ asset('storage/images/logos/logo.png') }}" alt="CONAR">
            </a>

            <a href="{{ route('nosotros') }}"
               class="{{ request()->routeIs('nosotros') ? 'active' : '' }}">{{ __('Nosotros') }}</a>

            <a href="{{ route('products.index') }}"
               class="{{ request()->routeIs('products.*') ? 'active' : '' }}">{{ __('Productos') }}</a>

            <a href="{{ route('industries.index') }}"
               class="{{ request()->routeIs('industries.*') ? 'active' : '' }}">{{ __('Industrias') }}</a>

            <a href="{{ route('posts.index') }}"
               class="{{ request()->routeIs('posts.*') ? 'active' : '' }}">{{ __('Novedades') }}</a>

            <a href="{{ route('contact.index') }}"
               class="{{ request()->routeIs('contact.*') ? 'active' : '' }}">{{ __('Contacto') }}</a>

            <a href="{{ route('search') }}" class="nav-search-icon" aria-label="{{ __('Buscar') }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21L16.66 16.66M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="{{ $isHome ? '#101010' : '#136CBA' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <!-- Presupuesto dentro del nav en mobile -->
            <a href="{{ route('presupuesto') }}" class="btn-presupuesto btn-presupuesto--mobile">
                {{ __('Presupuesto') }}
            </a>

        </nav>

        <div class="nav-utilities">
            <details class="language-switcher" aria-label="{{ __('Cambiar idioma') }}">
                <summary class="language-switcher__trigger">
                    <span class="language-switcher__badge">{{ $languages[$currentLocale]['short'] ?? 'ES' }}</span>
                    <span class="language-switcher__current">{{ $languages[$currentLocale]['label'] ?? 'Español' }}</span>
                    <span class="language-switcher__chevron" aria-hidden="true"></span>
                </summary>

                <div class="language-switcher__menu">
                    @foreach ($languages as $locale => $language)
                        <form method="POST" action="{{ route('language.switch', $locale) }}">
                            @csrf
                            <button
                                type="submit"
                                class="language-switcher__option{{ $currentLocale === $locale ? ' is-active' : '' }}"
                            >
                                <span>{{ $language['short'] }}</span>
                                <strong>{{ $language['label'] }}</strong>
                            </button>
                        </form>
                    @endforeach
                </div>
            </details>

            <!-- HAMBURGER (solo visible en mobile) -->
            <button class="hamburger" id="hamburgerBtn" aria-label="Abrir menú" aria-expanded="false">
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
                <span class="hamburger__line"></span>
            </button>
        </div>

        <!-- BOTÓN PRESUPUESTO (desktop only) -->
        <a href="{{ route('presupuesto') }}" class="btn-presupuesto btn-presupuesto--desktop">
            {{ __('Presupuesto') }}
        </a>

    </div>

</header>
