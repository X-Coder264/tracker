<tr>
    <td>XYZ</td>
    <td>
        <a href="{{ route('torrents.show', $torrent) }}">{{ $torrent->name }}</a>
        <br>
        {{ $torrent->created_at->timezone($timezone) }}
        {{ $torrent->created_at->diffForHumans() }}
    </td>
    <td>{{ $torrent->size }}</td>
    <td>{{ $torrent->seeders }}</td>
    <td>{{ $torrent->leechers }}</td>
    <td>{{ $torrent->uploader->name }}</td>
</tr>
