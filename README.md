# 📡 Laravel Public Realtime Chat with Laravel Reverb

This comprehensive guide will walk you through setting up a public real-time chat application using Laravel Reverb. By the end, you'll have a fully functional chat system with real-time message broadcasting.

## 🚀 Features
- Real-time message broadcasting
- User authentication integration
- Clean, modern UI
- Persistent message history

## 🛠 Prerequisites

- PHP 8.1 or higher
- Composer installed
- Laravel 10.0 or higher
- Node.js & NPM (for frontend assets)
- Basic Laravel knowledge (MVC, migrations, controllers)

## 🚀 Installation

1. First, create a new Laravel project or use an existing one:

```bash
composer create-project laravel/laravel laravel-reverb-chat
cd laravel-reverb-chat
```

2. Install the Laravel Reverb package:

```bash
php artisan install:broadcasting
```

This command will guide you through the setup process.
You will see prompts like the following in the terminal:
```bash
  Which broadcasting driver would you like to use?
  Laravel Reverb ............................................................................................................................ reverb  
  Pusher .................................................................................................................................... pusher  
  Ably ........................................................................................................................................ ably  
❯ reverb

  Would you like to install Laravel Reverb? (yes/no) [yes]
❯ yes
  Would you like to install Laravel Reverb? (yes/no) [yes]
❯ yes
```

## 💾 Database Setup

1. Set up your database in `.env`:

```env
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel_reverb
# DB_USERNAME=root
# DB_PASSWORD=
```

2. Create and run migrations for users (if not exists) and messages:

```bash
php artisan migrate
```

3. Create a `Message` model with migration:

```bash
php artisan make:model Message -m
```
Then update the generated migration file:

Migration example:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

```

Update the `Message` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message model.
 */
class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'body',
    ];

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### User Model

Update the `User` model to include the messages relationship:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * User model.
 */
class User extends Authenticatable
{
    // ... existing code ...

    /**
     * Get all messages sent by the user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
```
Create the event:
```bash
php artisan make:event MessageSent
```

## 📡 Broadcasting Events

### MessageSent Event

Create and configure the `MessageSent` event:

```bash
php artisan make:event MessageSent
```

Update the event class:

```php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * MessageSent event.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance.
     *
     * @var \App\Models\Message
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('public.chat'),
        ];
    }
    
    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'user' => $this->message->user->only('id', 'name'),
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
````
Create a message controller:
```php
<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

/**
 * MessageController.
 */
class MessageController extends Controller
{
    /**
     * Store a new message.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);
        $message = auth()->user()->messages()->create($data);
        broadcast(new MessageSent($message))->toOthers();
        $message->load('user');
        return response()->json($message);
    }
}

```

## 🌐 Routes Setup

### API Routes (`routes/api.php`)

```php
<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
});
```

### Web Routes (`routes/web.php`)

```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Home page with the chat interface
Route::view('/', 'chat');

// Authentication routes
Auth::routes();

// Broadcast authentication
Broadcast::routes(['middleware' => ['auth']]);
```

### Broadcast Channels (`routes/channels.php`)

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Public chat channel that anyone can join
Broadcast::channel('public.chat', function (User $user) {
    return ['id' => $user->id, 'name' => $user->name];
});
```

## 🎨 Frontend Implementation

### Chat View (`resources/views/chat.blade.php`)

Create a modern chat interface:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel Reverb Chat</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f3f4f6;
        }
        .chat-container {
            max-width: 800px;
            margin: 2rem auto;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .chat-header {
            background-color: #4f46e5;
            color: white;
            padding: 1rem;
            font-weight: bold;
        }
        .messages {
            height: 60vh;
            overflow-y: auto;
            padding: 1rem;
            background-color: white;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            max-width: 70%;
            word-wrap: break-word;
        }
        .message.sent {
            background-color: #e0e7ff;
            margin-left: auto;
        }
        .message.received {
            background-color: #f3f4f6;
            margin-right: auto;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .message-time {
            font-size: 0.75rem;
            color: #6b7280;
        }
        .input-area {
            display: flex;
            padding: 1rem;
            background-color: white;
            border-top: 1px solid #e5e7eb;
        }
        .message-input {
            flex-grow: 1;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-right: 0.5rem;
        }
        .send-button {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
        }
        .send-button:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body class="antialiased">
    <div class="chat-container">
        <div class="chat-header">
            Laravel Reverb Chat
            <span id="connection-status" class="text-sm font-normal">Connecting...</span>
        </div>
        
        <div class="messages" id="messages">
            <!-- Messages will be inserted here -->
        </div>
        
        <div class="input-area">
            <input 
                type="text" 
                id="message-input" 
                class="message-input" 
                placeholder="Type your message..."
                autofocus
            >
            <button id="send-button" class="send-button">
                Send
            </button>
        </div>
    </div>

    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            const connectionStatus = document.getElementById('connection-status');
            let currentUser = {!! auth()->user() ? json_encode([
                'id' => auth()->id(),
                'name' => auth()->user()->name
            ]) : 'null' !!};

            // Scroll to bottom of messages
            function scrollToBottom() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            // Add a new message to the chat
            function addMessage(message, isCurrentUser = false) {
                const messageClass = isCurrentUser ? 'sent' : 'received';
                const messageElement = document.createElement('div');
                messageElement.className = `message ${messageClass}`;
                
                const formattedTime = new Date(message.created_at).toLocaleTimeString([], { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                messageElement.innerHTML = `
                    <div class="message-header">
                        <span>${message.user.name}</span>
                        <span class="message-time">${formattedTime}</span>
                    </div>
                    <div class="message-body">${message.body}</div>
                `;
                
                messagesContainer.appendChild(messageElement);
                scrollToBottom();
            }

            // Load previous messages
            fetch('/api/messages')
                .then(response => response.json())
                .then(messages => {
                    messages.forEach(message => {
                        const isCurrentUser = currentUser && message.user.id === currentUser.id;
                        addMessage(message, isCurrentUser);
                    });
                });

            // Send message
            function sendMessage() {
                const message = messageInput.value.trim();
                if (message === '') return;

                // Add message to UI immediately
                const tempMessage = {
                    id: 'temp-' + Date.now(),
                    user: currentUser,
                    body: message,
                    created_at: new Date().toISOString()
                };
                
                addMessage(tempMessage, true);
                messageInput.value = '';

                // Send to server
                fetch('/api/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ body: message })
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    // Optionally show error to user
                });
            }

            // Event listeners
            sendButton.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Initialize Echo
            window.Echo.channel('public.chat')
                .listen('.MessageSent', (e) => {
                    const isCurrentUser = currentUser && e.user.id === currentUser.id;
                    if (!isCurrentUser) { // Don't show the message again if it's from the current user
                        addMessage(e, false);
                    }
                })
                .listenForWhisper('typing', (e) => {
                    // Handle typing indicators if needed
                });

            // Update connection status
            window.Echo.connector.socket.on('connect', () => {
                connectionStatus.textContent = 'Connected';
                connectionStatus.style.color = '#10b981'; // Green
            });

            window.Echo.connector.socket.on('disconnect', () => {
                connectionStatus.textContent = 'Disconnected';
                connectionStatus.style.color = '#ef4444'; // Red
            });

            // Initial scroll to bottom
            scrollToBottom();
        });
    </script>
</body>
</html>
```

## 🧪 Testing the Application

### Database Seeding

Update the `DatabaseSeeder` to create test users and messages:

```php
<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            
            // Create some test messages for each user
            Message::factory()->count(3)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
```

### Running the Application

1. First, install the frontend dependencies and build the assets:

```bash
npm install
npm run dev
```

2. In a new terminal, start the Laravel development server:

```bash
php artisan serve
```

3. In another terminal, start the Laravel Reverb server:

```bash
php artisan reverb:start --debug
```

4. Open two different browsers or incognito windows and log in with different user accounts to test the real-time chat functionality.

## 🔧 Troubleshooting

- If you get a 419 CSRF token mismatch, make sure the `@csrf` directive is present in your form or the CSRF token is included in your AJAX headers.
- If messages aren't appearing in real-time, check the browser's console for JavaScript errors.
- Ensure that the Laravel Reverb server is running and properly configured in your `.env` file.

## 🚀 Next Steps

- Add user presence detection
- Implement typing indicators
- Add message read receipts
- Support file attachments
- Add emoji support
- Implement private messaging

## 📚 Resources

- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Echo Documentation](https://laravel.com/docs/echo)

Happy coding! 🎉

