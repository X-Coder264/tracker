{!! '<?xml version="1.0" encoding="UTF-8" ?>' !!}

<rss version="2.0">
    <channel>
        <title>{{ $title }}</title>
        <description>{{ $description }}</description>
        <link>{{ $url }}</link>
        <item>
            @foreach ($items as $item)
                <title>{{ $item->getTitle() }}</title>
                <pubDate>{{ $item->getPubDate()->format('D, d M Y H:i:s O') }}</pubDate>
                <guid>{{ $item->getGuid() }}</guid>
                <enclosure url="{{ $item->getLink() }}" type="application/x-bittorrent" />
            @endforeach
        </item>
    </channel>
</rss>
