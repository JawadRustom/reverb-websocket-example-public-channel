# Laravel Realtime with Laravel Reverb

This guide will help you set up real-time functionality in your Laravel application using Laravel Reverb.

## Prerequisites
- Laravel 10.0 or higher
- PHP 8.1 or higher
- Composer installed

## Installation

1. First, install the Laravel Reverb package via Composer:

```bash
composer require laravel/reverb
```

2. Publish the Reverb configuration file:

```bash
php artisan reverb:install
```

3. Run database migrations:

```bash
php artisan migrate
```

## Configuration

1. Update your `.env` file with the following Reverb configuration:

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_APP_DEBUG=true
```

2. Configure your broadcasting settings in `config/broadcasting.php`:

```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME') === 'https',
        ],
    ],
],
```

## Setting Up Authentication

1. Ensure your User model is properly set up with the necessary traits:

```php
<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    
    // ... rest of your model code
}
```

## Running the Server

Start the Reverb server with the following command:

```bash
php artisan reverb:start
```

For production use, you may want to run this as a background process or use a process manager like Supervisor.

## Frontend Setup

1. Install the required JavaScript dependencies:

```bash
npm install --save-dev laravel-echo pusher-js
```

2. Configure Laravel Echo in your `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: window.location.hostname,
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});
```

## Testing Your Setup

1. Create an event:

```bash
php artisan make:event TestEvent
```

2. Update the event to implement `ShouldBroadcast`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('test-channel');
    }
}
```

3. Listen for the event in your JavaScript:

```javascript
window.Echo.channel('test-channel')
    .listen('.TestEvent', (e) => {
        console.log('Test Event:', e.message);
    });
```

4. Dispatch the event from your application:

```php
event(new TestEvent('Hello, WebSockets!'));
```

## Next Steps

- Set up private and presence channels for authenticated users
- Configure SSL for secure WebSocket connections in production
- Set up horizontal scaling for your WebSocket server
- Implement presence features for user presence tracking
