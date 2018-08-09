<br>
<div class="card">
    <div class="card-header">
        {{ $torrentComment->user->name }} - {{ $torrentComment->created_at->timezone($timezone) }} ({{ $torrentComment->created_at->diffForHumans() }})
        @if ($torrentComment->user_id === auth()->id())
            <a class="btn btn-primary" href="{{ route('torrent-comments.edit', $torrentComment) }}">Edit</a>
        @endif
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-2 d-none d-sm-block">
                <img src="https://i.imgur.com/eJhRU6c.jpg" class="img-fluid">
            </div>
            <div class="col-10">
                <p class="card-text">{{ $torrentComment->comment }}</p>
                @if ($torrentComment->updated_at->greaterThan($torrentComment->created_at))
                    <p><small>Edited {{ $torrentComment->updated_at->diffForHumans() }}</small></p>
                @endif
            </div>
        </div>
    </div>
</div>
