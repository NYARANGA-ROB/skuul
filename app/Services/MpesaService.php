<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;

class MpesaService
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $passkey;
    protected $shortCode;
    protected $env;
    protected $baseUrl;
    protected $accessToken;
    protected $timestamp;
    protected $initiatorName;
    protected $initiatorPassword;
    protected $securityCredential;
    protected $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->passkey = config('services.mpesa.passkey');
        $this->shortCode = config('services.mpesa.shortcode');
        $this->env = config('services.mpesa.env', 'sandbox');
        $this->initiatorName = config('services.mpesa.initiator_name');
        $this->initiatorPassword = config('services.mpesa.initiator_password');
        $this->securityCredential = $this->generateSecurityCredential();
        $this->callbackUrl = config('app.url') . '/api/mpesa/callback';
        
        $this->baseUrl = $this->env === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
            
        $this->authenticate();
    }

    protected function authenticate()
    {
        try {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

            if ($response->successful()) {
                $this->accessToken = $response->json()['access_token'];
                return true;
            }

            Log::error('M-Pesa Authentication Failed', [
                'response' => $response->json(),
                'status' => $response->status()
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('M-Pesa Authentication Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function generateSecurityCredential()
    {
        $publicKey = file_get_contents(storage_path('app/mpesa_cert.cer'));
        openssl_public_encrypt($this->initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    protected function getTimestamp()
    {
        $this->timestamp = date('YmdHis');
        return $this->timestamp;
    }

    protected function generatePassword()
    {
        $this->timestamp = $this->getTimestamp();
        $password = $this->shortCode . $this->passkey . $this->timestamp;
        return base64_encode($password);
    }

    public function stkPush($phone, $amount, $accountReference, $description = 'School Fees Payment')
    {
        try {
            $endpoint = '/mpesa/stkpush/v1/processrequest';
            $callbackUrl = $this->callbackUrl . '/stk';
            
            $phone = $this->formatPhoneNumber($phone);
            
            $payload = [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $this->generatePassword(),
                'Timestamp' => $this->timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone,
                'PartyB' => $this->shortCode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $description
            ];

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'data' => [
                        'merchant_request_id' => $responseData['MerchantRequestID'],
                        'checkout_request_id' => $responseData['CheckoutRequestID'],
                        'response_code' => $responseData['ResponseCode'],
                        'response_description' => $responseData['ResponseDescription'],
                        'customer_message' => $responseData['CustomerMessage']
                    ]
                ];
            }

            Log::error('M-Pesa STK Push Failed', [
                'payload' => $payload,
                'response' => $responseData,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Failed to initiate M-Pesa payment',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ];
        }
    }

    public function stkQuery($checkoutRequestId)
    {
        try {
            $endpoint = '/mpesa/stkpushquery/v1/query';
            
            $payload = [
                'BusinessShortCode' => $this->shortCode,
                'Password' => $this->generatePassword(),
                'Timestamp' => $this->timestamp,
                'CheckoutRequestID' => $checkoutRequestId
            ];

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['ResultCode'])) {
                return [
                    'success' => true,
                    'result_code' => $responseData['ResultCode'],
                    'result_desc' => $responseData['ResultDesc'] ?? '',
                    'data' => $responseData
                ];
            }

            Log::error('M-Pesa STK Query Failed', [
                'payload' => $payload,
                'response' => $responseData,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Failed to query M-Pesa payment status',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('M-Pesa STK Query Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while querying payment status',
                'error' => $e->getMessage()
            ];
        }
    }

    public function processCallback($callbackData, $type = 'stk')
    {
        try {
            Log::info('M-Pesa Callback Received', [
                'type' => $type,
                'data' => $callbackData
            ]);

            if ($type === 'stk') {
                return $this->processStkCallback($callbackData);
            }
            
            // Handle other callback types (C2B, B2C, etc.) here
            
            return [
                'success' => false,
                'message' => 'Unsupported callback type'
            ];
            
        } catch (\Exception $e) {
            Log::error('M-Pesa Callback Processing Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing callback',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function processStkCallback($callbackData)
    {
        $body = $callbackData['Body'];
        $resultCode = $body['stkCallback']['ResultCode'];
        $resultDesc = $body['stkCallback']['ResultDesc'] ?? '';
        $merchantRequestId = $body['stkCallback']['MerchantRequestID'] ?? '';
        $checkoutRequestId = $body['stkCallback']['CheckoutRequestID'] ?? '';
        
        // Find the payment record
        $payment = Payment::where('checkout_request_id', $checkoutRequestId)
            ->orWhere('merchant_request_id', $merchantRequestId)
            ->first();
            
        if (!$payment) {
            Log::error('Payment record not found for callback', [
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment record not found'
            ];
        }
        
        // Update payment status based on result code
        if ($resultCode == 0) {
            // Success
            $callbackMetadata = $body['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $mpesaReceiptNumber = '';
            $amount = 0;
            $phone = '';
            $transactionDate = '';
            
            foreach ($callbackMetadata as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'] ?? '';
                } elseif ($item['Name'] === 'Amount') {
                    $amount = $item['Value'] ?? 0;
                } elseif ($item['Name'] === 'PhoneNumber') {
                    $phone = $item['Value'] ?? '';
                } elseif ($item['Name'] === 'TransactionDate') {
                    $transactionDate = $item['Value'] ?? '';
                }
            }
            
            // Format transaction date
            $transactionDate = $transactionDate 
                ? Carbon::parse($transactionDate)->format('Y-m-d H:i:s')
                : now();
            
            // Update payment record
            $payment->update([
                'mpesa_receipt_number' => $mpesaReceiptNumber,
                'amount' => $amount,
                'phone_number' => $phone,
                'transaction_date' => $transactionDate,
                'status' => 'completed',
                'result_code' => $resultCode,
                'result_description' => $resultDesc
            ]);
            
            // Update related fee invoice
            if ($payment->feeInvoice) {
                $payment->feeInvoice->updateBalance($amount);
            }
            
            // Generate and send receipt
            $payment->generateAndSendReceipt();
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $payment->id,
                'receipt_number' => $mpesaReceiptNumber
            ];
            
        } else {
            // Payment failed
            $payment->update([
                'status' => 'failed',
                'result_code' => $resultCode,
                'result_description' => $resultDesc
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $resultDesc,
                'result_code' => $resultCode
            ];
        }
    }

    public function b2cPayment($phone, $amount, $commandId = 'BusinessPayment', $remarks = 'School Fees Refund')
    {
        try {
            $endpoint = '/mpesa/b2c/v1/paymentrequest';
            $callbackUrl = $this->callbackUrl . '/b2c';
            
            $phone = $this->formatPhoneNumber($phone);
            
            $payload = [
                'InitiatorName' => $this->initiatorName,
                'SecurityCredential' => $this->securityCredential,
                'CommandID' => $commandId,
                'Amount' => $amount,
                'PartyA' => $this->shortCode,
                'PartyB' => $phone,
                'Remarks' => $remarks,
                'QueueTimeOutURL' => $callbackUrl,
                'ResultURL' => $callbackUrl,
                'Occasion' => 'FeeRefund'
            ];

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);

            $responseData = $response->json();
            
            if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'data' => [
                        'conversation_id' => $responseData['ConversationID'],
                        'originator_conversation_id' => $responseData['OriginatorConversationID'],
                        'response_code' => $responseData['ResponseCode'],
                        'response_description' => $responseData['ResponseDescription']
                    ]
                ];
            }

            Log::error('M-Pesa B2C Payment Failed', [
                'payload' => $payload,
                'response' => $responseData,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => $responseData['errorMessage'] ?? 'Failed to process B2C payment',
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('M-Pesa B2C Payment Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing B2C payment',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function formatPhoneNumber($phone)
    {
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
        
        // Return as is if it doesn't match Kenyan number formats
        return $phone;
    }
}
