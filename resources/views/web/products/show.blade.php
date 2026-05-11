@extends('web.layouts.app')

@section('title', $product->name)

@section('content')

<section class="product-detail">

    <div class="container">

        {{-- BREADCRUMB --}}
        <div class="breadcrumb">
            <a href="{{ route('home') }}">{{ __('Inicio') }}</a>
            <span>›</span>
            <a href="{{ route('products.index') }}">{{ __('Instrumental') }}</a>
            <span>›</span>
            @if($product->subcategory && $product->subcategory->category)
            <a href="{{ route('products.category', $product->subcategory->category->slug) }}">{{ $product->subcategory->category->name }}</a>
            <span>›</span>
            <a href="{{ route('products.subcategory', [$product->subcategory->category->slug, $product->subcategory->slug]) }}">{{ $product->subcategory->name }}</a>
            <span>›</span>
            @endif
            <span>{{ $product->name }}</span>
        </div>

        {{-- MAIN LAYOUT: gallery + info --}}
        <div class="product-detail__layout">

            {{-- GALLERY --}}
            <div class="product-detail__gallery">

                <div class="product-detail__thumbs">
                    @if($product->images->count())
                    @foreach($product->images->sortBy('order') as $img)
                    @php
                    $isVideo = $img->isVideo();
                    $thumbSrc = $isVideo ? ($img->video_thumb ?? asset('images/video-placeholder.png')) : $img->image_url;
                    $dataSrc = $isVideo ? '' : $img->image_url;
                    $dataVideo = $isVideo ? $img->embed_url : '';
                    $dataType = $isVideo ? 'video' : 'image';
                    @endphp
                    <div class="product-detail__thumb {{ $loop->first ? 'active' : '' }}"
                        data-src="{{ $dataSrc }}"
                        data-video="{{ $dataVideo }}"
                        data-type="{{ $dataType }}">
                        <img src="{{ $thumbSrc }}" alt="{{ $product->name }}">
                        @if($isVideo)
                        <div class="product-detail__thumb-play">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="white">
                                <circle cx="12" cy="12" r="12" fill="rgba(0,0,0,0.5)" />
                                <polygon points="9,7 19,12 9,17" fill="white" />
                            </svg>
                        </div>
                        @endif
                    </div>
                    @endforeach
                    @else
                    @if($product->image_url)
                    <div class="product-detail__thumb active"
                        data-src="{{ $product->image_url }}"
                        data-type="image">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                    </div>
                    @endif
                    @endif
                </div>

                {{-- MAIN DISPLAY: image or video --}}
                <div class="product-detail__main-img" id="productMainDisplay">

                    @php
                    $firstMedia = $product->images->count() ? $product->images->sortBy('order')->first() : null;
                    $firstIsVideo = $firstMedia && $firstMedia->isVideo();
                    @endphp

                    @if($firstMedia)

                    {{-- MEDIA FROM GALLERY --}}
                    @if($firstIsVideo)

                    <iframe
                        src="{{ $firstMedia->embed_url }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>

                    @else

                    <img
                        src="{{ $firstMedia->image_url }}"
                        alt="{{ $product->name }}">

                    @endif


                    @elseif($product->video)

                    {{-- VIDEO LOCAL --}}
                    <video controls>
                        <source src="{{ asset('storage/'.$product->video) }}" type="video/mp4">
                    </video>


                    @elseif($product->video_url)

                    {{-- YOUTUBE --}}
                    <iframe
                        src="{{ str_replace('watch?v=', 'embed/', $product->video_url) }}"
                        frameborder="0"
                        allowfullscreen>
                    </iframe>


                    @elseif($product->image)

                    {{-- IMAGE FALLBACK --}}
                    <img
                        src="{{ $product->image_url }}"
                        alt="{{ $product->name }}">

                    @endif

                </div>

            </div>

            {{-- INFO --}}
            <div class="product-detail__info">
                @php
                $detailBody = $product->description ?: $product->short_description ?: $product->subtitle;
                $showSubtitle = $product->subtitle && trim((string) $product->subtitle) !== trim((string) $detailBody);
                @endphp

                <h1 class="product-detail__name">{{ $product->name }}</h1>

                @if($showSubtitle)
                <p class="product-detail__subtitle">{{ $product->subtitle }}</p>
                @endif

                @if($product->brand_logo)
                <div class="product-detail__brand">
                    <img src="{{ asset('storage/' . $product->brand_logo) }}" alt="{{ $product->brand }}">
                </div>
                @elseif($product->brand)
                <div class="product-detail__brand product-detail__brand--text">
                    <span>{{ $product->brand }}</span>
                </div>
                @endif

                @if($detailBody)
                <div class="product-detail__description">
                    {!! nl2br(e($detailBody)) !!}
                </div>
                @endif

                <div class="product-detail__actions">
                    @php
                    $download = $product->downloads
                        ->where('is_active', true)
                        ->where('is_principal', true)
                        ->first();

                    if (!$download) {
                        $download = $product->downloads
                            ->where('is_active', true)
                            ->sortBy('order')
                            ->first();
                    }
                    @endphp

                    @if($download)
                    <a href="{{ asset('storage/' . $download->file) }}"
                        download="Ficha-Tecnica-{{ Str::slug($product->name) }}.{{ strtolower($download->type) }}"
                        class="product-detail__btn product-detail__btn--outline">
                        {{ __('Ficha técnica') }}
                    </a>
                    @endif
                    <a href="{{ route('presupuesto') }}?product_id={{ $product->id }}"
                        class="product-detail__btn product-detail__btn--primary">
                        {{ __('Consultar') }}
                    </a>
                </div>

            </div>

        </div>

        {{-- TABS: specs + downloads --}}
        <div class="product-detail__tabs-section">

            <div class="product-detail__tabs">
                <button class="product-detail__tab active" data-tab="specs">{{ __('Características técnicas') }}</button>
                <button class="product-detail__tab" data-tab="downloads">{{ __('Descargas') }}</button>
            </div>

            <div class="product-detail__tab-content" id="tab-specs">
                @if($product->specifications->count())
                <div class="product-detail__specs">
                    @foreach($product->specifications->where('is_active', true)->sortBy('order') as $spec)
                    <div class="product-detail__spec-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect x="0.5" y="0.5" width="23" height="23" stroke="#136CBA" />
                            <path d="M9 12L11 14L15 10M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="#136CBA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ $spec->name }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="product-detail__tab-content hidden" id="tab-downloads">
                @if($product->downloads->where('is_active', true)->count())
                <div class="product-detail__downloads">
                    @foreach($product->downloads->where('is_active', true)->sortBy('order') as $dl)
                    <a href="{{ asset('storage/' . $dl->file) }}"
                        target="_blank"
                        class="product-detail__download-item">
                        <div class="product-detail__download-left">
                            <div class="product-detail__download-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="#136CBA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14 2V8H20" stroke="#136CBA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 18V12M9 15L12 18L15 15" stroke="#136CBA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div class="product-detail__download-info">
                                <span class="product-detail__download-name">{{ $dl->name }}</span>
                                @if($dl->type || $dl->size)
                                <span class="product-detail__download-meta">{{ $dl->type }}{{ $dl->size ? ' · ' . $dl->size : '' }}</span>
                                @endif
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" class="product-detail__download-arrow">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="#136CBA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M7 10L12 15L17 10M12 15V3" stroke="#136CBA" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        {{-- RELATED --}}
        @if($related->count())
        <div class="product-detail__related">
            <h2 class="product-detail__related-title">{{ __('Productos relacionados') }}</h2>
            <div class="products-grid">
                @foreach($related as $product)
                @include('web.products.components.product-card')
                @endforeach
            </div>
        </div>
        @endif

    </div>

</section>

@push('scripts')
<script>
    (function() {
        // Gallery thumbnails (images + videos)
        document.querySelectorAll('.product-detail__thumb').forEach(function(thumb) {
            thumb.addEventListener('click', function() {
                var display = document.getElementById('productMainDisplay');
                var type = this.dataset.type;

                document.querySelectorAll('.product-detail__thumb').forEach(function(t) {
                    t.classList.remove('active');
                });
                this.classList.add('active');

                if (type === 'video') {
                    display.innerHTML = '<iframe src="' + this.dataset.video + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:100%;border-radius:12px;"></iframe>';
                } else {
                    display.innerHTML = '<img id="productMainImg" src="' + this.dataset.src + '" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:12px;">';
                }
            });
        });

        // Tabs
        document.querySelectorAll('.product-detail__tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.product-detail__tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.product-detail__tab-content').forEach(function(c) {
                    c.classList.add('hidden');
                });
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
            });
        });
    })();
</script>
@endpush

@endsection
