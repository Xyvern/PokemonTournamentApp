<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    public function callback(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $payload = $request->all();
        
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        // 1. Verify Signature
        $calculatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($calculatedSignature !== $signatureKey) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. NEW: Find the transaction in your database
        $transaction = Transaction::where('order_id', $orderId)->first();
        
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found in database'], 404);
        }

        // 3. NEW: Security Check - Prevent Double Processing
        if ($transaction->status === 'settlement' || $transaction->status === 'capture') {
            return response()->json(['message' => 'Transaction already processed']);
        }

        // 4. Update the transaction record
        $transaction->status = $transactionStatus;
        $transaction->payment_type = $payload['payment_type'] ?? null;
        $transaction->save();

        // 5. Grant Premium if successful
        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $user = $transaction->user; // We can grab the user directly from the relationship now!
            
            if ($user) {
                if ($user->isPremium()) {
                    $user->premium_until = $user->premium_until->addDays(30);
                } else {
                    $user->premium_until = now()->addDays(30);
                }
                $user->save();
            }
        }

        return response()->json(['message' => 'Callback processed successfully']);
    }

    public function getSnapToken(Request $request)
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = false; 
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $orderId = 'PREM-' . Auth::id() . '-' . time();
        $amount = 50000;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount, 
            ],
            'customer_details' => [
                'first_name' => Auth::user()->nickname ?? Auth::user()->name,
                'email' => Auth::user()->email ?? 'player@example.com', 
            ],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        Transaction::create([
            'user_id' => Auth::id(),
            'order_id' => $orderId,
            'gross_amount' => $amount,
            'status' => 'pending',
            'snap_token' => $snapToken
        ]);

        return response()->json(['snapToken' => $snapToken]);
    }
}
