<!DOCTYPE html>
<html @if ($direction) dir="{{ $direction }}" @endif @if ($language) lang="{{ $language }}" @endif>
    <head>
        <meta charset="UTF-8">
        <title>{{ $title }}</title>

        {!! $head !!}
    </head>

    <body>
        {!! $layout !!}

        <div id="modal"></div>
        <div id="alerts"></div>

        <script>
            document.getElementById('talk-loading').style.display = 'block';
            var talk = {extensions: {}};
        </script>

        {!! $js !!}

        <script id="talk-json-payload" type="application/json">@json($payload)</script>

        <script>
            const data = JSON.parse(document.getElementById('talk-json-payload').textContent);
            document.getElementById('talk-loading').style.display = 'none';

            try {
                talkcenter.app.load(data);
                talkcenter.app.bootExtensions(talk.extensions);
                talkcenter.app.boot();
            } catch (e) {
                var error = document.getElementById('talk-loading-error');
                error.innerHTML += document.getElementById('talk-content').textContent;
                error.style.display = 'block';
                throw e;
            }
        </script>

        {!! $foot !!}
    </body>
</html>
