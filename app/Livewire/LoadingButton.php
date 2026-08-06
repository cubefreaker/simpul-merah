<?php

namespace App\Livewire;

use Livewire\Component;

class LoadingButton extends Component
{
    public $type = 'button';
    public $variant = 'primary';
    public $size = 'md';
    public $loadingText = null;
    public $disabled = false;
    public $text = 'Button';
    public $action = null;

    public function mount($type = 'button', $variant = 'primary', $size = 'md', $loadingText = null, $disabled = false, $text = 'Button', $action = null)
    {
        $this->type = $type;
        $this->variant = $variant;
        $this->size = $size;
        $this->loadingText = $loadingText;
        $this->disabled = $disabled;
        $this->text = $text;
        $this->action = $action;
    }

    public function render()
    {
        $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
        
        $variantClasses = [
            'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
            'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
            'info' => 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-400',
            'light' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus:ring-gray-500',
            'dark' => 'bg-gray-800 text-white hover:bg-gray-900 focus:ring-gray-700',
            'outline' => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500',
        ];
        
        $sizeClasses = [
            'xs' => 'px-2.5 py-1.5 text-xs',
            'sm' => 'px-3 py-2 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base',
            'xl' => 'px-8 py-4 text-lg',
        ];
        
        $classes = $baseClasses . ' ' . ($variantClasses[$this->variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$this->size] ?? $sizeClasses['md']);

        return view('livewire.components.loading-button', [
            'classes' => $classes
        ]);
    }

    public function triggerAction()
    {
        if ($this->action) {
            // Dispatch the event to the parent component
            $this->dispatch($this->action)->to('loading-demo');
        }
    }
} 