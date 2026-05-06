@extends('web.layouts.app')

@section('title','Productos')

@section('content')

<section class="products-page">

<div class="container">

<div class="breadcrumb">
<a href="{{ route('home') }}">Inicio</a>
<span>›</span>
<span>Instrumental</span>
</div>


<div class="products-layout">


{{-- SIDEBAR --}}

<aside class="products-sidebar">

<h3>Categorías</h3>

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

Seleccione una categoría para ver los productos.

</div>


</div>

</div>

</section>

@endsection