{{--
  Template Name: Home Template
--}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    {{-- @include('partials.page-header') --}}
    @include('partials/home.hero')
    @include('partials/home.oquee')
    {{-- @include('partials/home.comofunciona') --}}
    @include('partials/home.planos')
    {{-- @include('partials/home.faq') --}}
    @include('partials.content-page')
  @endwhile
@endsection
