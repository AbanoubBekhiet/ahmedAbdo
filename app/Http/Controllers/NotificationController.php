<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $messaging;

    public function __construct()
    {
        try {
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
            if (!file_exists($credentialsPath)) {
                Log::error('Firebase credentials file not found: ' . $credentialsPath);
                $this->messaging = null;
                return;
            }
            $factory = (new Factory())->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send a push notification to a specific user identified by their user_id.
     *
     * Accepts:
     *   - profile_id  (int)    : the user_id to notify
     *   - order_id    (int)    : the order id (used in notification body)
     *   - status      (string) : the message/status text to display
     *   - title       (string) : [optional] custom notification title
     *   - type        (string) : [optional] data payload type (default: 'order_status')
     *
     * This method is fully silent — it logs errors and never throws.
     */
    public function sendOrderStatusNotification(Request $request): void
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized, skipping notification.');
            return;
        }

        $profileId = $request->profile_id;
        $orderId   = $request->order_id;
        $status    = $request->status;
        $title     = $request->title ?? 'تحديث في حالة الطلب 📦';
        $type      = $request->type  ?? 'order_status';

        if (!$profileId || !$orderId || !$status) {
            Log::warning('sendOrderStatusNotification: missing required fields.', $request->all());
            return;
        }

        $profile = Profile::where('user_id', $profileId)->first();
        if (!$profile) {
            Log::warning("sendOrderStatusNotification: no profile for user_id={$profileId}");
            return;
        }

        $deviceToken = $profile->fcm_token;
        if (!$deviceToken) {
            Log::info("sendOrderStatusNotification: no FCM token for user_id={$profileId}");
            return;
        }

        try {
            $body = ($type === 'order_status')
                ? "حالة طلبك #{$orderId}: {$status}"
                : $status;

            $message = CloudMessage::new()
                ->withToken($deviceToken)
                ->withNotification(Notification::create($title, $body))
                ->withData([
                    'type'     => $type,
                    'order_id' => (string) $orderId,
                ]);

            $this->messaging->send($message);
            Log::info("Notification sent to user_id={$profileId} | type={$type} | status={$status}");
        } catch (\Exception $e) {
            Log::error("sendOrderStatusNotification failed for user_id={$profileId}: " . $e->getMessage());
        }
    }

    /**
     * Broadcast a global offer notification to all users subscribed to the 'offers' topic.
     * This method is fully silent — it logs errors and never throws.
     */
    public function sendGlobalOfferNotification(Request $request): void
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized, skipping offer notification.');
            return;
        }

        $title   = $request->title;
        $body    = $request->body;
        $offerId = $request->offer_id;

        if (!$title || !$body || !$offerId) {
            Log::warning('sendGlobalOfferNotification: missing required fields.', $request->all());
            return;
        }

        try {
            // 1. Send via Topic ('offers')
            $topicMessage = CloudMessage::new()
                ->withTopic('offers')
                ->withNotification(Notification::create($title, $body))
                ->withData([
                    'type'     => 'new_offer',
                    'offer_id' => (string) $offerId,
                ]);
            $this->messaging->send($topicMessage);
            Log::info("Offer notification broadcast to topic 'offers' | offer_id={$offerId} | title={$title}");
        } catch (\Exception $e) {
            Log::error("sendGlobalOfferNotification topic broadcast failed: " . $e->getMessage());
        }

        try {
            // 2. Also send directly to all FCM tokens in profiles to ensure maximum reliability
            $fcmTokens = Profile::whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->toArray();

            if (!empty($fcmTokens)) {
                foreach ($fcmTokens as $token) {
                    try {
                        $directMessage = CloudMessage::new()
                            ->withToken($token)
                            ->withNotification(Notification::create($title, $body))
                            ->withData([
                                'type'     => 'new_offer',
                                'offer_id' => (string) $offerId,
                            ]);
                        $this->messaging->send($directMessage);
                    } catch (\Exception $e) {
                        Log::warning("Failed to send offer notification to token: {$token} | Error: " . $e->getMessage());
                    }
                }
                Log::info("Offer notification sent to " . count($fcmTokens) . " individual tokens | offer_id={$offerId}");
            }
        } catch (\Exception $e) {
            Log::error("sendGlobalOfferNotification direct tokens send failed: " . $e->getMessage());
        }
    }
}