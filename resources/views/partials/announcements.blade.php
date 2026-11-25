{{-- Slim announcement bar under the navigation. $announcements comes from AppServiceProvider (View::composer). --}}
@if($announcements->isNotEmpty())
    <div class="container mt-3">
        <section class="announce-bar" aria-label="Obaveštenja">
            <div id="announceBar" class="carousel slide carousel-fade d-flex align-items-center" @if($announcements->count() > 1) data-bs-ride="carousel" data-bs-interval="5000" @endif>
                <div class="carousel-inner flex-grow-1 announce-slides">
                    @foreach($announcements as $announcement)
                        @php($url = $announcement->url())
                        @php($isBackground = $announcement->imageIsBackground())
                        <div @class(['carousel-item', 'active' => $loop->first])>
                            {{-- Image named "...background..." = whole slide background (inline style: the URL comes from the database) --}}
                            <div @class(['d-flex align-items-center gap-2 gap-md-3 px-3 py-2 announce-slide', 'announce-slide-bg' => $isBackground])
                                 @if($isBackground) style="{{ $announcement->backgroundStyle() }}" @endif>
                                {{-- Phones: only the pulsing dot (its color shows the type), so the title has room --}}
                                <span class="announce-type announce-kind-{{ $announcement->type }} flex-shrink-0" title="{{ $announcement->label() }}">
                                    <span class="announce-dot"></span><span class="d-none d-sm-inline">{{ $announcement->label() }}</span>
                                </span>

                                @if($announcement->imageUrl() && !$isBackground)
                                    <img src="{{ $announcement->imageUrl() }}" alt="" class="announce-thumb flex-shrink-0 d-none d-sm-block">
                                @endif

                                <div class="text-truncate flex-grow-1 small">
                                    <span class="announce-title">{{ $announcement->title }}</span>
                                    @if($announcement->body)
                                        <span class="announce-body d-none d-lg-inline ms-2">{{ $announcement->body }}</span>
                                    @endif
                                </div>

                                <span class="announce-countdown small text-nowrap flex-shrink-0">
                                    <span class="d-none d-md-inline">{{ $announcement->countdownLabel() }}</span>
                                    <strong data-countdown="{{ $announcement->ends_at->toIso8601String() }}" data-countdown-done="{{ $announcement->countdownDone() }}">{{ $announcement->ends_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</strong>
                                </span>

                                @if($url)
                                    <a href="{{ $url }}" class="btn btn-sale btn-sm flex-shrink-0 stretched-link">
                                        <span class="d-none d-sm-inline">Pogledaj</span><i class="bi bi-arrow-right d-sm-none"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($announcements->count() > 1)
                    <div class="d-none d-md-flex align-items-center gap-2 pe-3 flex-shrink-0 announce-nav">
                        <button type="button" class="btn btn-link p-0" data-bs-target="#announceBar" data-bs-slide="prev" aria-label="Prethodno obaveštenje">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="small">{{ $announcements->count() }}</span>
                        <button type="button" class="btn btn-link p-0" data-bs-target="#announceBar" data-bs-slide="next" aria-label="Sledeće obaveštenje">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @pushOnce('scripts', 'countdown')
        <script src="{{ asset('js/countdown.js') }}"></script>
    @endPushOnce
@endif
