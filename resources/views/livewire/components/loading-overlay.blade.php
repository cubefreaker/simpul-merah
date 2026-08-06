<div class="relative">
    @if($overlay)
        <div wire:loading.delay class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg z-10">
            @if($spinner)
                <div class="flex flex-col items-center space-y-2">
                    <div class="loading-spinner {{ $sizeClass }}"></div>
                    @if($text)
                        <p class="loading-text">{{ $text }}</p>
                    @endif
                </div>
            @else
                @if($text)
                    <p class="loading-text">{{ $text }}</p>
                @endif
            @endif
        </div>
    @endif
    
    {{ $slot }}
</div> 