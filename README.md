# Legal Drafting App

A Laravel Livewire application for legal document drafting and management.

## Features

- **Global Loading Overlay**: Automatic loading indicator for all Livewire requests
- **Wire:Loading Integration**: Comprehensive loading states throughout the application
- **User Management**: Role-based access control (Superadmin, Admin, User)
- **Form Submissions**: Document submission and management system
- **Group Management**: Organizational structure management
- **Modern UI**: Built with Tailwind CSS and Flux UI components

## Loading Functionality

The application includes a comprehensive loading system:

### Global Loading Overlay
- Uses Livewire's native `wire:loading.delay.longest` directive
- Automatically appears during Livewire requests longer than 500ms
- Centered spinner with "Loading..." text
- Semi-transparent backdrop with blur effect
- No JavaScript configuration required

### Wire:Loading Directives
- `wire:loading`: Show content during loading
- `wire:loading.remove`: Hide content during loading
- `wire:loading.attr`: Add/remove attributes during loading
- `wire:loading.class`: Add/remove classes during loading
- Various delay modifiers (short, shorter, shortest, delay, long, longer, longest)

### Pre-built Components
- **Loading Wrapper Component**: `@livewire('App\Livewire\LoadingWrapper')`
- **Loading Button Pattern**: Direct `wire:click` with loading states

### CSS Classes
- `.loading-spinner`: Animated spinner with size variants (sm, md, lg, xl)
- `.loading-text`: Styled loading text
- Component-specific loading classes (form-loading, card-loading, table-loading, nav-loading)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Build assets:
   ```bash
   npm run build
   ```
7. Start the development server:
   ```bash
   php artisan serve
   ```

## Usage

### Testing Loading Functionality

Visit `/loading-demo` to see all loading features in action:

- Basic loading examples with different durations
- Loading overlay component demonstrations
- Loading button component examples
- Form and table loading states
- Various loading spinner sizes

### Using Wire:Loading

```html
<!-- Basic loading state -->
<div wire:loading>
    Loading...
</div>

<!-- Button with loading state -->
<button wire:click="save" 
        wire:loading.attr="disabled"
        wire:loading.class="opacity-75">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>

<!-- Form with loading overlay -->
@livewire('loading-wrapper', ['text' => 'Saving form...'])
<form wire:submit="save">
    <!-- Form fields -->
</form>
```

### Using Loading Components

```html
<!-- Loading button -->
<button wire:click="save" 
        class="bg-blue-600 text-white hover:bg-blue-700 font-bold py-2 px-4 rounded"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-75">
    <span wire:loading.remove>Save Changes</span>
    <span wire:loading.delay>
        <div class="flex items-center space-x-2">
            <div class="loading-spinner loading-spinner-sm"></div>
            <span>Saving...</span>
        </div>
    </span>
</button>

<!-- Loading overlay -->
@livewire('loading-wrapper', [
    'size' => 'md',
    'text' => 'Loading data...'
])
<div class="p-4">
    <!-- Your content -->
</div>
```

## Documentation

For detailed documentation on the loading functionality, see [LOADING_GUIDE.md](LOADING_GUIDE.md).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). 