<?php

namespace App\Livewire;

use Livewire\Component;

class LoadingWrapper extends Component
{
    public $size = 'md';
    public $text = 'Loading...';
    public $overlay = true;
    public $spinner = true;

    public function mount($size = 'md', $text = 'Loading...', $overlay = true, $spinner = true)
    {
        $this->size = $size;
        $this->text = $text;
        $this->overlay = $overlay;
        $this->spinner = $spinner;
    }

    public function render()
    {
        $sizeClasses = [
            'sm' => 'h-4 w-4',
            'md' => 'h-6 w-6', 
            'lg' => 'h-8 w-8',
            'xl' => 'h-12 w-12'
        ];
        
        $sizeClass = $sizeClasses[$this->size] ?? $sizeClasses['md'];

        return view('livewire.components.loading-wrapper', [
            'sizeClass' => $sizeClass
        ]);
    }
} 