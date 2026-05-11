@extends('web.layouts.app')

@section('title', $industry->name)

@section('content')

<section class="industries-page">

<div class="container">

<div class="breadcrumb">
    <a href="{{ route('home') }}">{{ __('Inicio') }}</a>
    <span>›</span>
    <a href="{{ route('industries.index') }}">{{ __('Industrias') }}</a>
    <span>›</span>
    <span>{{ $industry->name }}</span>
</div>

<h1>{{ $industry->name }}</h1>

@if($industry->description)
<p>{{ $industry->description }}</p>
@endif

</div>

</section>

@endsection
