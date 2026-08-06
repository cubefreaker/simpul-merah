<button 
    type="{{ $type }}"
    class="{{ $classes }}"
    {{ $disabled ? 'disabled' : '' }}
    wire:click="triggerAction"
    wire:loading.attr="disabled"
    wire:loading.class="opacity-75 cursor-not-allowed"
>
    <span wire:loading.remove>
        {{ $text }}
    </span>
    
    <span wire:loading.delay>
        <div class="flex items-center space-x-2">
            <div class="loading-spinner loading-spinner-sm"></div>
            <span>{{ $loadingText ?? $text }}</span>
        </div>
    </span>
</button> 