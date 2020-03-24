<tr>
    <td>{{ $peer->torrent->category->name }}</td>
    <td>
        <a href="{{ route('torrents.show', $peer->torrent) }}">{{ $peer->torrent->name }}</a>
        <br>
        {{ $peer->torrent->created_at->timezone($timezone) }}
        {{ $peer->torrent->created_at->diffForHumans() }}
    </td>
    <td>{{ $peer->torrent->size }}</td>
    <td>{{ $peer->torrent->seeders }}</td>
    <td>{{ $peer->torrent->leechers }}</td>
    <td>{{ $peer->uploaded }}</td>
    <td>{{ $peer->downloaded }}</td>
    @if (0 === (int) $peer->getRawOriginal('downloaded'))
        <td>Inf.</td>
    @else
        <td>{{ number_format((int) $peer->getRawOriginal('uploaded') / (int) $peer->getRawOriginal('downloaded'), 2) }}</td>
    @endif
    <td>{{ $peer->updated_at->diffForHumans() }}</td>
    <td>{{ $peer->userAgent }}</td>
</tr>
