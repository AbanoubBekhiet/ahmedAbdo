<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Profile;

class NotificationController extends Controller
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory())->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
        $this->messaging = $factory->createMessaging();
    }

    public function sendOrderStatusNotification(Request $request)
    {
        $request->validate([
            'profile_id' => 'required|exists:profiles,user_id',
            'order_id' => 'required',
            'status' => 'required|string',
        ]);

        $user = Profile::where('user_id', $request->profile_id)->first();
        $deviceToken = $user->fcm_token; 

        if (!$deviceToken) {
            return response()->json(['message' => 'User does not have a registered device token.'], 404);
        }

        $message = CloudMessage::new()
            ->withToken($deviceToken)
            ->withNotification(Notification::create(
                'تحديث في حالة الطلب 📦', 
                "حالة طلبك #{$request->order_id} أصبحت: {$request->status}"
            ))
            ->withData([
                'type' => 'order_status',
                'order_id' => (string)$request->order_id,
            ]);

        try {
            $this->messaging->send($message);
            return response()->json(['message' => 'Order status notification sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send notification: ' . $e->getMessage()], 500);
        }
    }

    public function sendGlobalOfferNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'offer_id' => 'required',
        ]);

        $message = CloudMessage::new()
            ->withTopic('offers')
            ->withNotification(Notification::create(
                $request->title, 
                $request->body
            ))
            ->withData([
                'type' => 'new_offer',
                'offer_id' => (string)$request->offer_id,
            ]);

        try {
            $this->messaging->send($message);
            return response()->json(['message' => 'Global offer notification broadcasted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to broadcast offer: ' . $e->getMessage()], 500);
        }
    }
}