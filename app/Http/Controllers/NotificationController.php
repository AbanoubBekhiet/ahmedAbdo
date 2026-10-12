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
                ->withAndroidConfig($this->getAndroidConfig())
                ->withApnsConfig($this->getApnsConfig($title, $body))
                ->withData([
                    'type'         => $type,
                    'order_id'     => (string) $orderId,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
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
                ->withAndroidConfig($this->getAndroidConfig())
                ->withApnsConfig($this->getApnsConfig($title, $body))
                ->withData([
                    'type'         => 'new_offer',
                    'offer_id'     => (string) $offerId,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
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
                ->filter(fn($token) => is_string($token) && strlen(trim($token)) > 10)
                ->values()
                ->toArray();

            if (!empty($fcmTokens)) {
                $multicastMessage = CloudMessage::new()
                    ->withNotification(Notification::create($title, $body))
                    ->withAndroidConfig($this->getAndroidConfig())
                    ->withApnsConfig($this->getApnsConfig($title, $body))
                    ->withData([
                        'type'         => 'new_offer',
                        'offer_id'     => (string) $offerId,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]);

                try {
                    $report = $this->messaging->sendMulticast($multicastMessage, $fcmTokens);
                    Log::info("Offer notification multicast sent: {$report->successes()->count()} successful, {$report->failures()->count()} failed | offer_id={$offerId}");
                    $this->pruneStaleTokens($report);
                } catch (\Exception $e) {
                    Log::warning("Offer notification multicast failed, falling back to individual send: " . $e->getMessage());
                    foreach ($fcmTokens as $token) {
                        try {
                            $this->messaging->send($multicastMessage->withToken($token));
                        } catch (\Exception $subEx) {
                            Log::warning("Failed to send offer notification to token: {$token} | Error: " . $subEx->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("sendGlobalOfferNotification direct tokens send failed: " . $e->getMessage());
        }
    }

    /**
     * Send a notification to all admins and sub-admins about a new customer signup.
     */
    public function sendNewCustomerNotification($newCustomer): void
    {
        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized, skipping new customer notification.');
            return;
        }

        try {
            // Get FCM tokens for all admins and sub_admins
            $tokens = Profile::whereHas('user', function ($query) {
                    $query->whereIn('role', ['admin', 'sub_admin']);
                })
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->pluck('fcm_token')
                ->unique()
                ->filter(fn($token) => is_string($token) && strlen(trim($token)) > 10)
                ->values()
                ->toArray();

            if (empty($tokens)) {
                Log::warning("sendNewCustomerNotification: No active admin/sub_admin FCM tokens found for customer #{$newCustomer->id}.");
                return;
            }

            $customerName  = $newCustomer->name ?? 'عميل جديد';
            $customerPhone = $newCustomer->phone_number ?? 'غير متوفر';
            $title = "عميل جديد سجل في التطبيق 🎉";
            $body = "سجل العميل الجديد: {$customerName} (هاتف: {$customerPhone})";

            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withAndroidConfig($this->getAndroidConfig())
                ->withApnsConfig($this->getApnsConfig($title, $body))
                ->withData([
                    'type'           => 'new_customer_signup',
                    'user_id'        => (string) $newCustomer->id,
                    'customer_name'  => (string) $customerName,
                    'customer_phone' => (string) $customerPhone,
                    'click_action'   => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            try {
                $report = $this->messaging->sendMulticast($message, $tokens);
                Log::info("New customer signup notification sent for customer #{$newCustomer->id}: {$report->successes()->count()} successful, {$report->failures()->count()} failed.");
                $this->pruneStaleTokens($report);
            } catch (\Exception $e) {
                Log::warning("sendNewCustomerNotification multicast failed, falling back to individual send: " . $e->getMessage());
                foreach ($tokens as $token) {
                    try {
                        $this->messaging->send($message->withToken($token));
                    } catch (\Exception $subEx) {
                        Log::warning("Failed to send signup notification to token: {$token} | Error: " . $subEx->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("sendNewCustomerNotification failed for customer #{$newCustomer->id}: " . $e->getMessage());
        }
    }

    /**
     * Reusable Android configuration with high priority and notification channel.
     */
    protected function getAndroidConfig(): array
    {
        return [
            'priority' => 'high',
            'notification' => [
                'channel_id'              => 'high_importance_channel',
                'sound'                   => 'default',
                'default_sound'           => true,
                'default_vibrate_timings' => true,
                'notification_priority'   => 'PRIORITY_MAX',
                'visibility'              => 'PUBLIC',
                'click_action'            => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }

    /**
     * Reusable APNs configuration for iOS devices.
     */
    protected function getApnsConfig(string $title, string $body): array
    {
        return [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'sound'             => 'default',
                    'badge'             => 1,
                    'content-available' => 1,
                ],
            ],
        ];
    }

    /**
     * Automatically remove invalid or unregistered FCM tokens from profiles.
     */
    protected function pruneStaleTokens($report): void
    {
        try {
            if (is_object($report) && method_exists($report, 'unknownTokens') && method_exists($report, 'invalidTokens')) {
                $staleTokens = array_unique(array_merge($report->unknownTokens(), $report->invalidTokens()));
                if (!empty($staleTokens)) {
                    $count = Profile::whereIn('fcm_token', $staleTokens)->update(['fcm_token' => null]);
                    Log::info("Cleaned up {$count} invalid/stale FCM token(s) from profiles.");
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to prune stale FCM tokens: " . $e->getMessage());
        }
    }
}