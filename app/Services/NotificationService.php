<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Send payment receipt notification
     *
     * @param Payment $payment
     * @param array $channels
     * @return array
     */
    public function sendPaymentReceipt(Payment $payment, array $channels = ['email', 'sms'])
    {
        $user = $payment->user;
        $invoice = $payment->feeInvoice;
        $response = [];

        // Prepare common data for notifications
        $data = [
            'payment' => $payment,
            'user' => $user,
            'invoice' => $invoice,
            'amount' => number_format($payment->amount, 2),
            'receipt_number' => $payment->mpesa_receipt_number ?? $payment->id,
            'transaction_date' => $payment->transaction_date->format('d/m/Y H:i:s'),
            'balance' => $invoice ? number_format($invoice->balance, 2) : '0.00',
        ];

        // Send notifications through selected channels
        foreach ($channels as $channel) {
            try {
                $method = 'send' . ucfirst($channel) . 'Notification';
                if (method_exists($this, $method)) {
                    $response[$channel] = $this->$method($user, $data);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} notification: " . $e->getMessage());
                $response[$channel] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $response;
    }

    /**
     * Send email notification
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    protected function sendEmailNotification(User $user, array $data)
    {
        try {
            // Check if user has an email
            if (empty($user->email)) {
                return [
                    'success' => false,
                    'message' => 'User does not have an email address'
                ];
            }

            // Send the email
            Mail::send('emails.payment-receipt', $data, function ($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                    ->subject('Payment Receipt - ' . $data['receipt_number']);
            });

            return [
                'success' => true,
                'message' => 'Email notification sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS notification
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    protected function sendSmsNotification(User $user, array $data)
    {
        try {
            $phone = $this->formatPhoneNumber($user->phone);
            
            if (empty($phone)) {
                return [
                    'success' => false,
                    'message' => 'User does not have a valid phone number'
                ];
            }

            // Prepare the message
            $message = "Payment of KES {$data['amount']} received. " . 
                      "Receipt: {$data['receipt_number']}. " .
                      "New balance: KES {$data['balance']}. " .
                      "Thank you for paying with Skuul.";

            // Send SMS using Africa's Talking API (or your preferred SMS gateway)
            $response = Http::withHeaders([
                'apiKey' => config('services.africastalking.api_key'),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ])->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to' => $phone,
                'message' => $message,
                'from' => config('app.name')
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->json()
                ];
            } else {
                throw new \Exception($response->body());
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send WhatsApp notification
     *
     * @param User $user
     * @param array $data
     * @return array
     */
    protected function sendWhatsappNotification(User $user, array $data)
    {
        try {
            $phone = $this->formatPhoneNumber($user->phone);
            
            if (empty($phone)) {
                return [
                    'success' => false,
                    'message' => 'User does not have a valid phone number for WhatsApp'
                ];
            }

            // Prepare the message
            $message = "*Payment Receipt*\n\n" .
                      "*Amount:* KES {$data['amount']}\n" .
                      "*Receipt No:* {$data['receipt_number']}\n" .
                      "*Date:* {$data['transaction_date']}\n" .
                      "*New Balance:* KES {$data['balance']}\n\n" .
                      "Thank you for paying with Skuul. This is an automated message.";

            // Send WhatsApp message using Twilio API (or your preferred WhatsApp API)
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            )->asForm()->post(
                "https://api.twilio.com/2010-04-01/Accounts/" . config('services.twilio.sid') . "/Messages.json",
                [
                    'From' => 'whatsapp:' . config('services.twilio.whatsapp_from'),
                    'To' => 'whatsapp:' . $phone,
                    'Body' => $message
                ]
            );

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully',
                    'response' => $response->json()
                ];
            } else {
                throw new \Exception($response->body());
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp message sending failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to international format
     *
     * @param string $phone
     * @return string|null
     */
    protected function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }

        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Kenyan numbers (add 254 if it's a 0 or 254 prefix)
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            return $phone;
        } elseif (strlen($phone) === 9) {
            return '254' . $phone;
        }

        return $phone;
    }

    /**
     * Send low balance notification
     *
     * @param User $user
     * @param float $balance
     * @param array $channels
     * @return array
     */
    public function sendLowBalanceNotification(User $user, float $balance, array $channels = ['email', 'sms'])
    {
        $data = [
            'user' => $user,
            'balance' => number_format($balance, 2),
            'date' => now()->format('d/m/Y H:i:s')
        ];

        $response = [];
        foreach ($channels as $channel) {
            try {
                $method = 'send' . ucfirst($channel) . 'LowBalanceAlert';
                if (method_exists($this, $method)) {
                    $response[$channel] = $this->$method($user, $data);
                }
            } catch (\Exception $e) {
                Log::error("Failed to send {$channel} low balance alert: " . $e->getMessage());
                $response[$channel] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $response;
    }

    /**
     * Send low balance email alert
     */
    protected function sendEmailLowBalanceAlert(User $user, array $data)
    {
        if (empty($user->email)) {
            return [
                'success' => false,
                'message' => 'User does not have an email address'
            ];
        }

        try {
            Mail::send('emails.low-balance', $data, function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Low Balance Alert - ' . config('app.name'));
            });

            return [
                'success' => true,
                'message' => 'Low balance email alert sent'
            ];
        } catch (\Exception $e) {
            Log::error('Low balance email alert failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send low balance email alert'
            ];
        }
    }

    /**
     * Send low balance SMS alert
     */
    protected function sendSmsLowBalanceAlert(User $user, array $data)
    {
        $phone = $this->formatPhoneNumber($user->phone);
        
        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'User does not have a valid phone number'
            ];
        }

        $message = "Low balance alert! Your account balance is KES {$data['balance']}. " . 
                  "Please top up to avoid service interruption. " .
                  "Thank you for using " . config('app.name') . ".";

        try {
            // Send SMS using Africa's Talking API
            $response = Http::withHeaders([
                'apiKey' => config('services.africastalking.api_key'),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ])->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to' => $phone,
                'message' => $message,
                'from' => config('app.name')
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Low balance SMS alert sent',
                    'response' => $response->json()
                ];
            } else {
                throw new \Exception($response->body());
            }
        } catch (\Exception $e) {
            Log::error('Low balance SMS alert failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send low balance SMS alert'
            ];
        }
    }
}
