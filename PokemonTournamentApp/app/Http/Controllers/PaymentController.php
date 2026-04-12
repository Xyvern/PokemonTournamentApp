<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        
        // 1. Grab the payload sent by Midtrans
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        // 2. Security Check: Verify the Signature Key
        // Midtrans calculates: SHA512(order_id + status_code + gross_amount + server_key)
        $calculatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($calculatedSignature !== $signatureKey) {
            Log::error('Midtrans Signature Verification Failed', $payload);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 3. Process the Payment Status
        // 'settlement' or 'capture' means the money successfully arrived
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            
            // Extract the user ID from our custom Order ID (e.g., "PREM-5-1712345678" -> "5")
            $parts = explode('-', $orderId);
            $userId = $parts[1] ?? null;

            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    // Add 30 days of Premium!
                    // If they already have premium, add 30 days to their current expiration date.
                    // If they don't, add 30 days from right now.
                    if ($user->isPremium()) {
                        $user->premium_until = $user->premium_until->addDays(30);
                    } else {
                        $user->premium_until = now()->addDays(30);
                    }
                    $user->save();

                    Log::info("Premium granted to User ID: {$userId}");
                }
            }
        }

        // 4. Always return a 200 OK so Midtrans knows you received the ping
        return response()->json(['message' => 'Callback received successfully']);
    }
}
