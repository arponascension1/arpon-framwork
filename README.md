# Arpon Framework

A modern, Laravel-inspired PHP framework featuring advanced routing, Eloquent ORM, dependency injection, and comprehensive middleware support.

## Features

### Core Features
- ✅ **Service Container** - Powerful dependency injection container
- ✅ **Eloquent ORM** - Complete database abstraction with relationships
- ✅ **Advanced Routing** - Laravel-style routing with 14+ features
- ✅ **Middleware Pipeline** - Flexible request/response handling
- ✅ **Authentication System** - Guards, providers, and session management
- ✅ **Validation** - Request validation with multiple rules
- ✅ **View System** - Template engine with layouts and sections
- ✅ **Console/Artisan** - CLI commands for development tasks
- ✅ **Events & Listeners** - Event-driven architecture
- ✅ **File Storage** - Abstract filesystem operations
- ✅ **Mail System** - Send emails with PHPMailer integration
- ✅ **Session Management** - Secure session handling
- ✅ **CSRF Protection** - Built-in security features
- ✅ **Logging** - Application logging support

### Advanced Routing Features

#### 1. Resource Routes
```php
Route::resource('users', UserController::class);
// Generates all 7 RESTful routes automatically
```

#### 2. API Resource Routes
```php
Route::apiResource('posts', PostController::class);
// Like resource but excludes create/edit forms
```

#### 3. Implicit Model Binding
```php
Route::get('/users/{user}', function(User $user) {
    return $user;
});
// Automatically queries User::find($id)
```

#### 4. Explicit Model Binding
```php
Route::model('user', User::class);
Route::bind('user', function($value) {
    return User::where('slug', $value)->firstOrFail();
});
```

#### 5. Route Constraints
```php
Route::get('/users/{id}', UserController::class)->whereNumber('id');
Route::get('/posts/{slug}', PostController::class)->whereAlpha('slug');
Route::get('/api/{uuid}', ApiController::class)->whereUuid('uuid');
```

#### 6. Optional Parameters
```php
Route::get('/profile/{name?}', function($name = 'Guest') {
    return "Hello, $name!";
});
```

#### 7. Route Groups
```php
Route::prefix('admin')->middleware(['auth'])->group(function() {
    Route::get('/dashboard', DashboardController::class);
    Route::resource('users', UserController::class);
});
```

#### 8. Named Routes
```php
Route::get('/profile', ProfileController::class)->name('profile');
// Generate URLs: route('profile')
```

#### 9. Route Caching
```bash
php artisan route:cache   # Cache routes for production
php artisan route:clear   # Clear route cache
php artisan route:list    # List all routes
```

#### 10. Subdomain Routing
```php
Route::domain('{account}.example.com')->group(function() {
    Route::get('/', function($account) {
        return "Welcome to $account subdomain";
    });
});
```

#### 11. Fallback Routes
```php
Route::fallback(function() {
    return view('errors.404');
});
```

#### 12. Route Macros
```php
Route::macro('admin', function($uri, $action) {
    return Route::get("admin/$uri", $action)->middleware(['auth', 'admin']);
});

Route::admin('dashboard', DashboardController::class);
```

#### 13. Match Multiple Methods
```php
Route::match(['GET', 'POST'], '/form', FormController::class);
Route::any('/webhook', WebhookController::class); // All HTTP methods
```

#### 14. Redirect & View Routes
```php
Route::redirect('/old', '/new', 301);
Route::permanentRedirect('/legacy', '/current');
Route::view('/about', 'about', ['title' => 'About Us']);
```

## Installation

### As a Framework Core

This repository contains the framework core files. To use it in your application:

```bash
# In your application directory
git clone https://github.com/arponascension1/arpon-framwork.git src
```

### Example Application

For a complete example application, see:
[Arpon MVC Application](https://github.com/arponascension1/arpon-mvc)

## Directory Structure

```
src/Arpon/
├── Auth/                 # Authentication system
├── Config/               # Configuration management
├── Console/              # Artisan commands
├── Container/            # Service container & DI
├── Contracts/            # Interfaces
├── Database/             # Database, Query Builder, Eloquent ORM
│   ├── Eloquent/        # Eloquent models & relationships
│   ├── Query/           # Query builder
│   └── Schema/          # Schema builder & migrations
├── Events/               # Event dispatcher
├── Filesystem/           # File storage abstraction
├── Foundation/           # Application core
├── Hashing/              # Password hashing
├── Http/                 # HTTP layer
│   ├── Middleware/      # HTTP middleware
│   └── Exceptions/      # HTTP exceptions
├── Log/                  # Logging system
├── Mail/                 # Email system
├── Pipeline/             # Middleware pipeline
├── Routing/              # Router & routes
├── Security/             # CSRF protection
├── Session/              # Session management
├── Support/              # Helper classes & facades
│   └── Facades/         # Facade classes
├── Validation/           # Request validation
└── View/                 # View engine
```

## Usage

### Basic Application Setup

```php
// bootstrap/app.php
require_once __DIR__ . '/../vendor/autoload.php';

$app = new \Arpon\Foundation\Application(
    basePath: dirname(__DIR__)
);

return $app;
```

### Defining Routes

```php
// routes/web.php
use Arpon\Support\Facades\Route;

Route::get('/', function() {
    return view('welcome');
});

Route::resource('posts', PostController::class);
```

### Creating Controllers

```php
namespace App\Http\Controllers;

use App\Models\User;
use Arpon\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', ['users' => $users]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);
        
        User::create($request->all());
        return redirect('/users');
    }
    
    public function edit(User $user) // Implicit model binding
    {
        return view('users.edit', ['user' => $user]);
    }
}
```

### Eloquent Models

```php
namespace App\Models;

use Arpon\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

### Middleware

```php
namespace App\Http\Middleware;

use Closure;
use Arpon\Http\Request;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}
```

### Views

```php
<!-- resources/views/users/index.php -->
<?php layout('layouts.app'); ?>

<?php section('content'); ?>
    <h1>Users</h1>
    <?php foreach($users as $user): ?>
        <p><?= $user->name ?></p>
    <?php endforeach; ?>
<?php endSection(); ?>
```

### Console Commands

```bash
php artisan serve              # Start development server
php artisan migrate            # Run migrations
php artisan route:list         # List all routes
php artisan route:cache        # Cache routes
php artisan make:migration     # Create migration
```

## Requirements

- PHP 8.0 or higher
- PDO extension
- mbstring extension
- OpenSSL extension

## Eloquent ORM Features

- **Relationships**: hasOne, hasMany, belongsTo, belongsToMany, hasManyThrough, morphOne, morphMany, morphToMany
- **Query Scopes**: Global and local scopes
- **Soft Deletes**: SoftDeletes trait
- **Timestamps**: Automatic created_at and updated_at
- **Mass Assignment Protection**: $fillable and $guarded
- **Attribute Casting**: Cast attributes to specific types
- **Events**: Model events (creating, created, updating, updated, etc.)

## Documentation

For complete documentation and examples, see the example application:
[Arpon MVC Application](https://github.com/arponascension1/arpon-mvc)

## Credits

Inspired by Laravel Framework and built with modern PHP practices.

## License

Open-source software.

## Author

**Arpon Ascension**
- GitHub: [@arponascension1](https://github.com/arponascension1)
- Email: arponascension20@gmail.com
