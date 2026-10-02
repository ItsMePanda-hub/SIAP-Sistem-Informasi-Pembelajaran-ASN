<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function publicKey()
    {
        $key = config('webpush.public_key');

        if (empty($key)) {
            return response()->json(['key' => null], 503);
        }

        return response()->json(['key' => $key]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2000',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $hash = hash('sha256', $validated['endpoint']);

        $sub = PushSubscription::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint_hash' => $hash],
            ['endpoint' => $validated['endpoint'], 'p256dh' => $validated['keys']['p256dh'], 'auth' => $validated['keys']['auth']]
        );

        return response()->json(['id' => $sub->id]);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:2000',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['success' => true]);
    }
}
