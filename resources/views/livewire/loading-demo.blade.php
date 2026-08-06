<?php

use function Livewire\Volt\{state, layout, on};

layout('livewire.layouts.app');

state(['message' => '', 'loading' => false]);

$simulateLoading = function($duration = 2000) {
    $this->loading = true;
    $this->message = "Loading for {$duration}ms...";
    
    // Simulate async operation
    usleep($duration * 1000);
    
    $this->message = "Operation completed after {$duration}ms!";
    $this->loading = false;
};

$fastOperation = function() {
    $this->simulateLoading(500);
};

$slowOperation = function() {
    $this->simulateLoading(3000);
};

$verySlowOperation = function() {
    $this->simulateLoading(5000);
};

// Event listeners are no longer needed since we're using direct method calls

?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-6">Loading Demo</h2>
                
                <!-- Message Display -->
                @if($message)
                    <div class="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                        {{ $message }}
                    </div>
                @endif
                
                <!-- Basic Loading Examples -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Basic Loading Examples</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Fast Operation -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Fast Operation (500ms)</h4>
                            <button wire:click="fastOperation" 
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                                <span wire:loading.remove>Start Fast</span>
                                <span wire:loading.delay>
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="loading-spinner loading-spinner-sm"></div>
                                        <span>Running...</span>
                                    </div>
                                </span>
                            </button>
                        </div>
                        
                        <!-- Slow Operation -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Slow Operation (3s)</h4>
                            <button wire:click="slowOperation" 
                                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                                <span wire:loading.remove>Start Slow</span>
                                <span wire:loading.delay>
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="loading-spinner loading-spinner-sm"></div>
                                        <span>Running...</span>
                                    </div>
                                </span>
                            </button>
                        </div>
                        
                        <!-- Very Slow Operation -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Very Slow (5s)</h4>
                            <button wire:click="verySlowOperation" 
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                                <span wire:loading.remove>Start Very Slow</span>
                                <span wire:loading.delay>
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="loading-spinner loading-spinner-sm"></div>
                                        <span>Running...</span>
                                    </div>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Overlay Component Demo -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Loading Overlay Component</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Small Overlay -->
                        <div class="border rounded-lg p-4 bg-gray-50 relative">
                            @livewire('App\Livewire\LoadingWrapper', ['size' => 'sm', 'text' => 'Loading small...'])
                            <h4 class="font-medium mb-2">Small Loading Overlay</h4>
                            <p class="text-sm text-gray-600 mb-4">This content will be covered by a small loading overlay when any Livewire action is triggered.</p>
                            <button wire:click="slowOperation" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Trigger Loading
                            </button>
                        </div>
                        
                        <!-- Large Overlay -->
                        <div class="border rounded-lg p-4 bg-gray-50 relative">
                            @livewire('App\Livewire\LoadingWrapper', ['size' => 'lg', 'text' => 'Loading large...'])
                            <h4 class="font-medium mb-2">Large Loading Overlay</h4>
                            <p class="text-sm text-gray-600 mb-4">This content will be covered by a large loading overlay when any Livewire action is triggered.</p>
                            <button wire:click="slowOperation" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Trigger Loading
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Button Component Demo -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Loading Button Component</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button wire:click="slowOperation" 
                                class="bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Primary Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Processing...</span>
                                </div>
                            </span>
                        </button>
                        
                        <button wire:click="fastOperation" 
                                class="bg-green-600 text-white hover:bg-green-700 font-bold py-2 px-4 rounded"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Success Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Saving...</span>
                                </div>
                            </span>
                        </button>
                        
                        <button wire:click="verySlowOperation" 
                                class="bg-red-600 text-white hover:bg-red-700 font-bold py-2 px-4 rounded"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Danger Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Deleting...</span>
                                </div>
                            </span>
                        </button>
                        
                        <button wire:click="slowOperation" 
                                class="bg-yellow-600 text-white hover:bg-yellow-700 font-bold py-2 px-4 rounded"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Warning Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Updating...</span>
                                </div>
                            </span>
                        </button>
                        
                        <button wire:click="slowOperation" 
                                class="border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-2 px-4 rounded"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Outline Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Submitting...</span>
                                </div>
                            </span>
                        </button>
                        
                        <button wire:click="slowOperation" 
                                class="bg-gray-600 text-white hover:bg-gray-700 font-bold py-3 px-6 rounded text-base"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75">
                            <span wire:loading.remove>Large Button</span>
                            <span wire:loading.delay>
                                <div class="flex items-center space-x-2">
                                    <div class="loading-spinner loading-spinner-sm"></div>
                                    <span>Processing...</span>
                                </div>
                            </span>
                        </button>
                    </div>
                </div>
                
                <!-- Loading States Demo -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Loading States</h3>
                    
                    <div class="space-y-4">
                        <!-- Form Loading -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Form Loading State</h4>
                            <form wire:submit="slowOperation" class="form-loading space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           wire:loading.attr="disabled"
                                           wire:loading.class="opacity-50">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           wire:loading.attr="disabled"
                                           wire:loading.class="opacity-50">
                                </div>
                                
                                <button type="submit" 
                                        class="bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75">
                                    <span wire:loading.remove>Submit Form</span>
                                    <span wire:loading.delay>
                                        <div class="flex items-center space-x-2">
                                            <div class="loading-spinner loading-spinner-sm"></div>
                                            <span>Submitting...</span>
                                        </div>
                                    </span>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Table Loading -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Table Loading State</h4>
                            <div class="table-loading">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Doe</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button wire:click="slowOperation" 
                                                        class="text-blue-600 hover:text-blue-900"
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="opacity-50">
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Spinners Demo -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Loading Spinners</h3>
                    
                    <div class="flex items-center space-x-8">
                        <div class="flex items-center space-x-2">
                            <div class="loading-spinner loading-spinner-sm"></div>
                            <span class="text-sm">Small</span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <div class="loading-spinner loading-spinner-md"></div>
                            <span class="text-sm">Medium</span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <div class="loading-spinner loading-spinner-lg"></div>
                            <span class="text-sm">Large</span>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <div class="loading-spinner loading-spinner-xl"></div>
                            <span class="text-sm">Extra Large</span>
                        </div>
                    </div>
                </div>
                
                <!-- Livewire Native Loading Examples -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Livewire Native Loading Patterns</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Target Loading -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Target Loading</h4>
                            <p class="text-sm text-gray-600 mb-4">Loading states that target specific elements</p>
                            
                            <div class="space-y-2">
                                <button wire:click="slowOperation" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full"
                                        wire:target="slowOperation">
                                    <span wire:loading.remove wire:target="slowOperation">Target Loading</span>
                                    <span wire:loading wire:target="slowOperation">
                                        <div class="flex items-center justify-center space-x-2">
                                            <div class="loading-spinner loading-spinner-sm"></div>
                                            <span>Loading...</span>
                                        </div>
                                    </span>
                                </button>
                                
                                <div wire:loading wire:target="slowOperation" class="text-sm text-blue-600">
                                    This text only shows when slowOperation is running
                                </div>
                            </div>
                        </div>
                        
                        <!-- Different Delays -->
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-2">Different Delays</h4>
                            <p class="text-sm text-gray-600 mb-4">Loading states with different delay times</p>
                            
                            <div class="space-y-2">
                                <button wire:click="fastOperation" 
                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full">
                                    <span wire:loading.remove wire:loading.delay>Fast (200ms delay)</span>
                                    <span wire:loading wire:loading.delay>Loading...</span>
                                </button>
                                
                                <button wire:click="slowOperation" 
                                        class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full">
                                    <span wire:loading.remove wire:loading.longest>Slow (500ms delay)</span>
                                    <span wire:loading wire:loading.longest>Loading...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="bg-gray-50 border rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-2">How to Use</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Click any button to see the loading states in action</li>
                        <li>• The global loading overlay uses <code>wire:loading.delay.longest</code> (500ms delay)</li>
                        <li>• Use <code>wire:target</code> to target specific actions</li>
                        <li>• Use different delay modifiers to control when loading shows</li>
                        <li>• Refer to LOADING_GUIDE.md for detailed documentation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div> 