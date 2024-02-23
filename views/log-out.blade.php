@extends('talk.site::layouts.basic')

@section('title', $translator->trans('talk.views.log_out.title'))

@section('content')
  <p>{{ $translator->trans('talk.views.log_out.log_out_confirmation', ['site' => $settings->get('site_title')]) }}</p>

  <p>
    <a href="{{ $url }}" class="button">
      {{ $translator->trans('talk.views.log_out.log_out_button') }}
    </a>
  </p>
@endsection
