@inject('url', 'Talk\Http\UrlGenerator')

<div class="container">
    <h2>{{ $translator->trans('talk.views.admin.title') }}</h2>

    <table class="NoJs-InfoTable Table">
        <caption><h3>{{ $translator->trans('talk.views.admin.info.caption') }}</h3></caption>
        <tbody>
            <tr>
                <td>Talk</td>
                <td>{{ $talkVersion }}</td>
            </tr>
        <tr>
            <td>PHP</td>
            <td>{{ $phpVersion }}</td>
        </tr>
        <tr>
            <td>MySQL</td>
            <td>{{ $mysqlVersion }}</td>
        </tr>
        </tbody>
    </table>

    <table class="NoJs-ExtensionsTable Table">
        <caption><h3>{{ $translator->trans('talk.views.admin.extensions.caption') }}</h3></caption>
        <thead>
            <tr>
                <th></th>
                <th>{{ $translator->trans('talk.views.admin.extensions.name') }}</th>
                <th>{{ $translator->trans('talk.views.admin.extensions.package_name') }}</th>
                <th>{{ $translator->trans('talk.views.admin.extensions.version') }}</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($extensions as $extension)
                @php $isEnabled = in_array($extension->getId(), $extensionsEnabled); @endphp

                <tr>
                    <td class="NoJs-ExtensionsTable-icon">
                        <div class="ExtensionIcon" style="{{ $extension->getIconStyles() }}">
                            <span class="icon {{ $extension->getIcon()['name'] ?? '' }}"></span>
                        </div>
                    </td>
                    <td class="NoJs-ExtensionsTable-title">{{ $extension->getTitle() }}</td>
                    <td class="NoJs-ExtensionsTable-name">{{ $extension->name }}</td>
                    <td class="NoJs-ExtensionsTable-version">{{ $extension->getVersion() }}</td>
                    <td class="NoJs-ExtensionsTable-state">
                        <span class="ExtensionListItem-Dot {{ $isEnabled ? 'enabled' : 'disabled' }}" aria-hidden="true"></span>
                    </td>
                    <td class="NoJs-ExtensionsTable-toggle Table-controls">
                        <form action="{{ $url->to('admin')->route('extensions.update', ['name' => $extension->getId()]) }}" method="POST">
                            <input type="hidden" name="csrfToken" value="{{ $csrfToken }}">
                            <input type="hidden" name="enabled" value="{{ $isEnabled ? 0 : 1 }}">

                            @if($isEnabled)
                                <button type="submit" class="Button Table-controls-item">{{ $translator->trans('talk.views.admin.extensions.disable') }}</button>
                            @else
                                <button type="submit" class="Button Table-controls-item">{{ $translator->trans('talk.views.admin.extensions.enable') }}</button>
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="NoJs-ExtensionsTable-empty">{{ $translator->trans('talk.views.admin.extensions.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
