<a href="{{ route('products.show',$product->slug) }}" class="product-card" style="--cat-color: {{ $product->category->color ?? '#28597D' }}">
    @php
    $cardImage = $product->imageUrl;

    if (!$cardImage && $product->relationLoaded('images') && $product->images->count()) {
        $firstImage = $product->images->sortBy('order')->first();
        $cardImage = $firstImage?->image_url;
    }
    @endphp

    <div class="product-card-image">

        @if($cardImage)
        <img src="{{ $cardImage }}" alt="{{ $product->name }}">
        @endif

        <div class="product-card-overlay">
            <span class="product-card-plus">+</span>
        </div>

    </div>

    <div class="product-card-body">

        <span class="product-category">
            {{ strtoupper($product->category->name ?? '') }}
        </span>

        <h3 class="product-title">
            {{ $product->name }}
        </h3>

    </div>

</a>
