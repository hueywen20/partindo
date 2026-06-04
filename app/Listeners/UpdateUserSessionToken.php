<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Str;

class UpdateUserSessionToken
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void {
        /** @var \App\Models\User $user */
        $user = $event->user;

        $token = \Illuminate\Support\Str::uuid()->toString();

        // Now that the IDE knows $user is \App\Models\User, 
        // it will recognize both 'session_token' and 'save()'
        $user->session_token = $token;
        $user->save();

        session(['session_token' => $token]);
        
        Log::info("Session token updated to {$token} for user {$user->id}");
    }
}
