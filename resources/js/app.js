// Livewire Loading Overlay using Livewire's native approach
document.addEventListener('livewire:init', () => {
    // Use Livewire's built-in loading states
    Livewire.on('loading', () => {
        // This will be handled by wire:loading directives
    });
});
