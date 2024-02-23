<div id="talk-loading" style="display: none">
    {{ $translator->trans('talk.views.content.loading_text') }}
</div>

<noscript>
    <div class="Alert">
        <div class="container">
            {{ $translator->trans('talk.views.content.javascript_disabled_message') }}
        </div>
    </div>
</noscript>

<div id="talk-loading-error" style="display: none">
    <div class="Alert">
        <div class="container">
            {{ $translator->trans('talk.views.content.load_error_message') }}
        </div>
    </div>
</div>

<noscript id="talk-content">
    {!! $content !!}
</noscript>
