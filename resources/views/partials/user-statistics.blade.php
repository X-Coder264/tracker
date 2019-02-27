@if (auth()->check())
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-body">
                    {{ trans('messages.common.uploaded') }}: {{ auth()->user()->uploaded }} |
                    {{ trans('messages.common.downloaded') }}: {{ auth()->user()->downloaded }} |
                    {{ trans('messages.common.seeding') }}: {{ $numberOfSeedingTorrents }} |
                    {{ trans('messages.common.leeching') }}: {{ $numberOfLeechingTorrents }} |
                    <a href="{{ route('threads.index') }}">
                        <span style="font-size: 1.5em; color: @if ($hasUnreadThreads) Tomato; @else Blue; @endif">
                            <i class="fas fa-envelope"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <br>
@endif
