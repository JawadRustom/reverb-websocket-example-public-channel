# Laravel Realtime with Laravel Reverb

This guide will help you set up real-time functionality in your Laravel application using Laravel Reverb.

## Prerequisites
- Laravel 10.0 or higher
- PHP 8.1 or higher
- Composer installed

## Installation

1. First, install the Laravel Reverb package via Composer:

```bash
php artisan install:broadcasting
```
and you will have this condition in terminal
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
now add database
2. create message model with migration:

```bash
php artisan make:model Message -m
```
in migration add 

1. Update your `.env` file with the following Reverb configuration:

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
and in message model add this

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'body',
    ];
    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

```

and in user model add this

```php

       public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
```
now add this 
```bash
php artisan make:event MessageSent
```

now config MessageSent 

```php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $message;
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
    public function broadcastWith(): array
    {
        return [
          'id'=>$this->message->id,
          'user'=>$this->message->user->only('id','name'),
          'body'=>$this->message->body,
          'created_at'=>$this->message->created_at->toDateTimeString(),
        ];
    }
}

```
make message controller
```php
<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);
        $message = auth()->user()->messages()->create($data);
        boroadcast(new MessageSent($message))->toOthers();
        $message->load('user');
        return response()->json($message);
    }
}

```

go to api.php

```php
Route::post('/message', [\App\Http\Controllers\Api\MessageController::class, 'store']);
```
test this code with blade
got to web.php 
```php
Route::view('/listen', 'listen');
Broadcast::routes();
```

now add this code to channels.php 
```php
Broadcast::channel('public.chat', function ($user) {
    return true;
});
```

now create blade to test listen.blade.php
```php
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reverb Listener</title>
    @vite('resources/js/app.js') {{-- contains Echo setup; see below --}}
</head>

<body class="font-sans antialiased">
<h1 id="status">Waiting for messages…</h1>
<ul id="messages"></ul>

<script>
    setTimeout(() => {
        Echo.channel('public.chat')
            .listen('MessageSent', e => {
                document.getElementById('status').textContent = 'Real-time connected ✔';
                document.getElementById('messages').insertAdjacentHTML(
                    'beforeend',
                    `<li><strong>${e.user.name}</strong>: <br>${e.body} <br><em>${e.time}</em></li>`
                );
                console.log('Event received', e);
            });

    }, 500);
</script>
</body>

</html>
```

and here add seeder to test
```php
        User::factory()->create([
            'name' => 'User1',
            'email' => 'user1@user.com',
        ]);
        
        User::factory()->create([
            'name' => 'User2',
            'email' => 'user2@user.com',
        ]);
```
now start this command
```bash
php artisan reverb:start --debug
php artisan queue:work
```
