<?php
require 'vendor/autoload.php'; // Ensure you have the necessary autoloaders

use Kreait\Firebase\Factory;

function sendPushNotification($fcmToken, $title, $body, $data) {
    $factory = (new Factory)
        ->withServiceAccount(__DIR__.'/push-notification-5ce03-firebase-adminsdk-6mh0o-e23e907724.json')
        ->withDatabaseUri('https://60.51.93.226.firebaseio.com');

    $messaging = $factory->createMessaging();

    $message = [
        'token' => $fcmToken,
        'notification' => [
            'title' => $title,
            'body' => $body,
        ],
        'data' => $data,
    ];

    try {
        $messaging->send($message);
        return true;
    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        error_log('Failed to send push notification: ' . $e->getMessage());
        // Handle specific error codes
        if ($e->getCode() === 'INVALID_ARGUMENT') {
            error_log('Invalid argument provided.');
        } elseif ($e->getCode() === 'UNAUTHENTICATED') {
            error_log('Authentication error. Check your credentials.');
        }
        return false;
    } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
        error_log('Firebase error: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log('General error: ' . $e->getMessage());
        return false;
    }
}
?>
