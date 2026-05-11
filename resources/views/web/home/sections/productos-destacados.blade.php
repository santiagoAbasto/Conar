@if($featuredProducts->count())

<section class="productos-destacados">

    <div class="container">

        <h2 class="productos-destacados__titulo">
            {{ __('Productos destacados') }}
        </h2>

        <div class="productos-destacados__grid">

            @foreach($featuredProducts as $item)

            <a href="{{ $item['url'] }}" class="product-card" style="--cat-color: {{ $item['category_color'] ?? '#28597D' }}">

                {{-- IMAGEN + OVERLAY HOVER --}}
                <div class="product-card__img-wrap">

                    @if($item['image'])
                        <img
                            src="{{ $item['image'] }}"
                            alt="{{ $item['name'] }}"
                            class="product-card__img"
                            loading="lazy">
                    @else
                        <img
                            src="{{ asset('images/placeholder-product.png') }}"
                            alt="{{ $item['name'] }}"
                            class="product-card__img"
                            loading="lazy">
                    @endif

                    <div class="product-card__overlay">
                        <span class="product-card__plus">+</span>
                    </div>

                </div>

                {{-- INFO --}}
                <div class="product-card__body">

                    @if($item['category'])
                    <span class="product-card__category">
                        {{ strtoupper($item['category']) }}
                    </span>
                    @endif

                    <h3 class="product-card__name">
                        {{ $item['name'] }}
                    </h3>

                </div>

            </a>

            @endforeach

        </div>

    </div>

</section>

@endif
