@extends('talk.site::layouts.basic')

@section('content')
  <p>
    {{ $message }}
  </p>
  <p>
    <a href="{{ $url->to('site')->base() }}">
      {{ $translator->trans('talk.views.error.not_found_return_link', ['site' => $settings->get('site_title')]) }}
    </a>
  </p>
@endsection
