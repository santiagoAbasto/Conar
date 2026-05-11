@extends('web.layouts.app')

@section('title', __('Productos'))

@section('content')

<section class="products-page">

<div class="container">

<div class="breadcrumb">
<a href="{{ route('home') }}">{{ __('Inicio') }}</a>
<span>›</span>
<span>{{ __('Instrumental') }}</span>
</div>


<div class="products-layout">


{{-- SIDEBAR --}}

<aside class="products-sidebar">

<h3>{{ __('Categorías') }}</h3>

<ul>

@foreach($categories as $category)

<li style="--cat-color: {{ $category->color }}">

<a href="{{ route('products.category',$category->slug) }}">

{{ strtoupper($category->name) }}

</a>

</li>

@endforeach

</ul>

</aside>


<div class="products-empty">

{{ __('Seleccione una categoría para ver los productos.') }}

</div>


</div>

</div>

</section>

@endsection
