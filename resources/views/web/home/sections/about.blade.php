@php
$about = $page->sections
    ->where('type','about')
    ->where('is_active',true)
    ->first();

$aboutTitle = $about?->getTitle() ?? '';
$aboutContent = $about?->getContent() ?? '';
$aboutButton = $about?->getButtonText() ?? '';
@endphp

@if($about)

<section class="about-section">

    {{-- IMAGEN: absoluta, crece desde el borde izquierdo del viewport --}}
    <div class="about-section__img-wrap">
        <img
            src="{{ asset('storage/' . $about->image) }}"
            alt="{{ $aboutTitle ?: __('Nosotros') }}"
            class="about-section__img">
    </div>

    {{-- INNER: centrado max 1366px, padding-left 645px reserva el espacio de la imagen --}}
    <div class="about-section__inner">
        <div class="about-section__content">

            <h2 class="about-section__title">
                {{ $aboutTitle }}
            </h2>

            @foreach(array_filter(array_map('trim', explode("\n", $aboutContent))) as $paragraph)
            <p class="about-section__text">{{ $paragraph }}</p>
            @endforeach

            @if($aboutButton)
            <a href="{{ $about->button_link ?? '#' }}" class="about-section__btn">
                {{ $aboutButton }}
            </a>
            @endif

        </div>
    </div>

</section>

@endif
