{!! '<?xml version="1.0" encoding="UTF-8" ?>' !!}

<rss version="2.0">
    <channel>
        <title>{{ $title }}</title>
        <description>{{ $description }}</description>
        <link>{{ $url }}</link>
        @foreach ($items as $item)
            <item>
                <title>{{ $item->getTitle() }}</title>
                <pubDate>{{ $item->getPubDate()->toRssString() }}</pubDate>
                <guid>{{ $item->getGuid() }}</guid>
                <enclosure url="{{ $item->getLink() }}" type="application/x-bittorrent" />
            </item>
        @endforeach
    </channel>
</rss>
