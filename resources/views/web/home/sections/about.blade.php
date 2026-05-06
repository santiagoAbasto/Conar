@php
$about = $page->sections
    ->where('type','about')
    ->where('is_active',true)
    ->first();
@endphp

@if($about)

<section class="about-section">

    {{-- IMAGEN: absoluta, crece desde el borde izquierdo del viewport --}}
    <div class="about-section__img-wrap">
        <img
            src="{{ asset('storage/' . $about->image) }}"
            alt="{{ $about->title['es'] ?? 'Sobre nosotros' }}"
            class="about-section__img">
    </div>

    {{-- INNER: centrado max 1366px, padding-left 645px reserva el espacio de la imagen --}}
    <div class="about-section__inner">
        <div class="about-section__content">

            <h2 class="about-section__title">
                {{ $about->title['es'] ?? '' }}
            </h2>

            @foreach(array_filter(array_map('trim', explode("\n", $about->content['es'] ?? ''))) as $paragraph)
            <p class="about-section__text">{{ $paragraph }}</p>
            @endforeach

            @if($about->button_text)
            <a href="{{ $about->button_link ?? '#' }}" class="about-section__btn">
                {{ $about->button_text['es'] ?? '' }}
            </a>
            @endif

        </div>
    </div>

</section>

@endif
