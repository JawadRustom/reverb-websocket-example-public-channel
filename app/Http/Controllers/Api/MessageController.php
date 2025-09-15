<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    //store a new message
    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'user_id' => 'required|exists:users,id',
        ]);

        // Create a new message
        $message = Message::create($data);

        // Dispatch the MessageSent broadcast event
        broadcast(new MessageSent($message))->toOthers();
        $message->load('user'); // Load the user relationship for the response

        return response()->json($message, 201);
    }
}
