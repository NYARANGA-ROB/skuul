<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\FeeInvoice;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MpesaController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate STK push payment
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stkPush(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
            'amount' => 'required|numeric|min:1|max:150000',
            'invoice_id' => 'required|exists:fee_invoices,id',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $invoice = FeeInvoice::findOrFail($request->invoice_id);
            
            // Verify the invoice belongs to the user or user has permission
            if ($invoice->user_id !== $user->id && !$user->hasRole(['admin', 'bursar'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to pay this invoice'
                ], 403);
            }

            // Check if invoice is already paid
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This invoice has already been paid'
                ], 400);
            }

            // Create a payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'fee_invoice_id' => $invoice->id,
                'amount' => $request->amount,
                'balance' => $invoice->balance,
                'status' => 'pending',
                'payment_mode' => 'mpesa',
                'phone_number' => $request->phone,
                'transaction_date' => now(),
            ]);

            // Generate a unique reference
            $accountReference = 'SKUUL-' . $payment->id . '-' . Str::random(4);
            
            // Initiate STK push
            $response = $this->mpesaService->stkPush(
                $request->phone,
                $request->amount,
                $accountReference,
                $request->description ?? 'School Fees Payment'
            );

            // Update payment with M-Pesa details
            if ($response['success']) {
                $payment->update([
                    'merchant_request_id' => $response['data']['merchant_request_id'],
                    'checkout_request_id' => $response['data']['checkout_request_id']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $response['data']['customer_message'],
                    'data' => [
                        'payment_id' => $payment->id,
                        'checkout_request_id' => $payment->checkout_request_id
                    ]
                ]);
            }

            // Update payment status if STK push failed
            $payment->update([
                'status' => 'failed',
                'result_description' => $response['message']
            ]);

            return response()->json([
                'success' => false,
                'message' => $response['message'],
                'data' => $response['response'] ?? null
            ], 400);

        } catch (\Exception $e) {
            Log::error('STK Push Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle M-Pesa callback
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleCallback(Request $request, $type = 'stk')
    {
        try {
            $data = $request->all();
            
            // Log the callback for debugging
            Log::info('M-Pesa Callback Received', [
                'type' => $type,
                'data' => $data
            ]);

            // Process the callback based on type
            $result = $this->mpesaService->processCallback($data, $type);

            if ($result['success']) {
                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'ThirdPartyTransID' => $result['receipt_number'] ?? ''
                ]);
            }

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => $result['message'] ?? 'Failed to process payment',
                'error' => $result['error'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Callback Processing Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Error processing callback',
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Check payment status
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
            'checkout_request_id' => 'required_without:payment_id|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Find the payment
            $payment = $request->has('payment_id')
                ? Payment::findOrFail($request->payment_id)
                : Payment::where('checkout_request_id', $request->checkout_request_id)->firstOrFail();
            
            // Verify the payment belongs to the user or user has permission
            if ($payment->user_id !== $user->id && !$user->hasRole(['admin', 'bursar'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this payment'
                ], 403);
            }

            // If payment is already completed, return the status
            if (in_array($payment->status, ['completed', 'failed', 'cancelled'])) {
                return $this->formatPaymentResponse($payment);
            }

            // Query M-Pesa for status if we have a checkout request ID
            if ($payment->checkout_request_id) {
                $status = $this->mpesaService->stkQuery($payment->checkout_request_id);
                
                if ($status['success']) {
                    // Update payment status based on M-Pesa response
                    if ($status['result_code'] == '0') {
                        $payment->update(['status' => 'completed']);
                    } elseif (in_array($status['result_code'], ['1032', '1037'])) {
                        // Payment cancelled by user
                        $payment->update([
                            'status' => 'cancelled',
                            'result_description' => $status['result_desc']
                        ]);
                    } else {
                        // Other error
                        $payment->update([
                            'status' => 'failed',
                            'result_description' => $status['result_desc']
                        ]);
                    }
                }
            }

            return $this->formatPaymentResponse($payment->fresh());

        } catch (\Exception $e) {
            Log::error('Payment Status Check Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Format payment response
     */
    protected function formatPaymentResponse($payment)
    {
        $response = [
            'success' => $payment->status === 'completed',
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'mpesa_receipt_number' => $payment->mpesa_receipt_number,
                'phone_number' => $payment->phone_number,
                'transaction_date' => $payment->transaction_date,
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ],
            'invoice' => null,
            'receipt_url' => null
        ];

        // Add invoice details if available
        if ($payment->feeInvoice) {
            $response['invoice'] = [
                'id' => $payment->feeInvoice->id,
                'invoice_number' => $payment->feeInvoice->invoice_number,
                'total_amount' => $payment->feeInvoice->total_amount,
                'balance' => $payment->feeInvoice->balance,
                'status' => $payment->feeInvoice->status,
                'due_date' => $payment->feeInvoice->due_date,
            ];

            // Add receipt URL if payment is completed
            if ($payment->status === 'completed' && $payment->receipt_sent) {
                $response['receipt_url'] = route('api.payments.receipt', $payment->id);
            }
        }

        return response()->json($response);
    }

    /**
     * Get payment receipt
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getReceipt($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            // Verify the payment belongs to the user or user has permission
            if (auth()->check() && $payment->user_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'bursar'])) {
                abort(403, 'Unauthorized to view this receipt');
            }

            // Generate receipt if not already generated
            if (!$payment->receipt_sent) {
                $payment->generateAndSendReceipt();
                $payment->refresh();
            }

            // Return the receipt file (implement this based on your receipt storage)
            $receiptPath = storage_path('app/receipts/receipt_' . $payment->id . '.pdf');
            
            if (file_exists($receiptPath)) {
                return response()->file($receiptPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="receipt-' . $payment->mpesa_receipt_number . '.pdf"'
                ]);
            }

            // If receipt file doesn't exist, generate it
            $payment->generateAndSendReceipt();
            
            if (file_exists($receiptPath)) {
                return response()->file($receiptPath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="receipt-' . $payment->mpesa_receipt_number . '.pdf"'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Receipt not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Receipt Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate receipt',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
