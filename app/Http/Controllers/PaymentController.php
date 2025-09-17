<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
        $this->middleware('auth');
    }

    /**
     * Show the M-Pesa payment form for a specific invoice
     *
     * @param int $invoiceId
     * @return \Illuminate\View\View
     */
    public function showMpesaForm($invoiceId)
    {
        $invoice = FeeInvoice::with(['student', 'fee'])
            ->where('id', $invoiceId)
            ->firstOrFail();

        // Check if user is authorized to view this invoice
        $this->authorize('view', $invoice);

        return view('payments.mpesa', [
            'invoice' => $invoice
        ]);
    }

    /**
     * Handle M-Pesa payment callback from Safaricom
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleMpesaCallback(Request $request)
    {
        // Log the callback for debugging
        Log::info('M-Pesa Callback Received:', $request->all());

        // Verify the callback is from Safaricom
        if (!$this->mpesaService->verifyCallback($request->all())) {
            Log::warning('Invalid M-Pesa callback received', [
                'ip' => $request->ip(),
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid callback'], 400);
        }

        try {
            // Process the callback
            $result = $this->mpesaService->processCallback($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Callback processed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing M-Pesa callback: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing callback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status
     *
     * @param string $checkoutRequestId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus($checkoutRequestId)
    {
        try {
            $status = $this->mpesaService->checkStkStatus($checkoutRequestId);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage(), [
                'checkoutRequestId' => $checkoutRequestId
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment receipt
     *
     * @param int $paymentId
     * @return \Illuminate\Http\Response
     */
    public function getReceipt($paymentId)
    {
        $payment = \App\Models\Payment::findOrFail($paymentId);
        
        // Check if user is authorized to view this receipt
        $this->authorize('view', $payment);
        
        if (empty($payment->receipt_path) || !file_exists(storage_path('app/' . $payment->receipt_path))) {
            abort(404, 'Receipt not found');
        }
        
        return response()->file(storage_path('app/' . $payment->receipt_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-' . $payment->id . '.pdf"'
        ]);
    }
}
