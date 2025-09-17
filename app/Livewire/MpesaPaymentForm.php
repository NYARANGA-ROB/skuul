<?php

namespace App\Livewire;

use App\Models\FeeInvoice;
use App\Services\MpesaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MpesaPaymentForm extends Component
{
    use WithFileUploads;

    public $invoice;
    public $amount;
    public $phoneNumber;
    public $paymentPlan = 'full';
    public $installmentCount = 1;
    public $installmentAmounts = [];
    public $showInstallmentForm = false;
    public $isProcessing = false;
    public $paymentStatus = null;
    public $paymentMessage = '';
    public $receipt = null;

    protected $listeners = ['paymentProcessed' => 'handlePaymentResponse'];

    public function mount($invoiceId)
    {
        $this->invoice = FeeInvoice::with(['student', 'fee'])->findOrFail($invoiceId);
        $this->amount = $this->invoice->balance;
        $this->phoneNumber = $this->getUserPhoneNumber();
        
        // Initialize installment amounts
        $this->updateInstallmentAmounts();
    }

    protected function getUserPhoneNumber()
    {
        $user = Auth::user();
        
        // If user has a phone number, format it for M-Pesa (starts with 254...)
        if ($user->phone_number) {
            $phone = preg_replace('/\D/', '', $user->phone_number);
            
            // Convert to 254... format if it's a Kenyan number
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                return '254' . substr($phone, 1);
            }
            
            return $phone;
        }
        
        return '';
    }

    protected function rules()
    {
        return [
            'phoneNumber' => ['required', 'string', 'regex:/^(?:254|0)?[17]\d{8}$/'],
            'amount' => [
                'required', 
                'numeric', 
                'min:1', 
                'max:' . $this->invoice->balance,
                function ($attribute, $value, $fail) {
                    if ($value > $this->invoice->balance) {
                        $fail('The payment amount cannot exceed the invoice balance.');
                    }
                },
            ],
            'paymentPlan' => ['required', 'in:full,installment'],
            'installmentCount' => ['required_if:paymentPlan,installment', 'integer', 'min:2', 'max:12'],
            'installmentAmounts.*' => ['required_if:paymentPlan,installment', 'numeric', 'min:1'],
        ];
    }

    protected $messages = [
        'phoneNumber.regex' => 'Please enter a valid Kenyan phone number (e.g., 0712345678 or 254712345678).',
        'amount.max' => 'The payment amount cannot exceed the invoice balance of KES :max.',
        'installmentAmounts.*.required_if' => 'Each installment amount is required.',
        'installmentAmounts.*.numeric' => 'Installment amounts must be numbers.',
        'installmentAmounts.*.min' => 'Each installment must be at least KES 1.',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        
        if (in_array($propertyName, ['paymentPlan', 'installmentCount', 'amount'])) {
            $this->updateInstallmentAmounts();
        }
    }

    protected function updateInstallmentAmounts()
    {
        if ($this->paymentPlan === 'installment' && $this->installmentCount > 1) {
            $amountPerInstallment = $this->amount / $this->installmentCount;
            $this->installmentAmounts = array_fill(0, $this->installmentCount, number_format($amountPerInstallment, 2, '.', ''));
        } else {
            $this->installmentAmounts = [number_format($this->amount, 2, '.', '')];
        }
    }

    public function toggleInstallmentForm()
    {
        $this->showInstallmentForm = !$this->showInstallmentForm;
        $this->updateInstallmentAmounts();
    }

    public function initiatePayment()
    {
        $this->validate();
        
        // Format phone number for M-Pesa (254XXXXXXXXX)
        $phone = $this->formatPhoneNumber($this->phoneNumber);
        
        try {
            $this->isProcessing = true;
            
            // Initialize MpesaService
            $mpesaService = app(MpesaService::class);
            
            // If it's an installment payment, create a payment plan first
            if ($this->paymentPlan === 'installment' && count($this->installmentAmounts) > 1) {
                $this->createInstallmentPlan();
                $this->paymentMessage = 'Installment plan created successfully. Proceeding with first payment...';
                $this->amount = (float) $this->installmentAmounts[0];
            }
            
            // Initiate STK Push
            $response = $mpesaService->stkPush(
                phone: $phone,
                amount: $this->amount,
                accountReference: 'INV-' . $this->invoice->id,
                description: 'Fee Payment for ' . $this->invoice->fee->name,
                callbackUrl: route('api.mpesa.callback')
            );
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']);
            }
            
            $this->paymentStatus = 'pending';
            $this->paymentMessage = 'Payment request sent to your phone. Please enter your M-Pesa PIN to complete the transaction.';
            
            // Start polling for payment status
            $this->pollPaymentStatus($response['CheckoutRequestID']);
            
        } catch (\Exception $e) {
            $this->paymentStatus = 'failed';
            $this->paymentMessage = 'Error: ' . $e->getMessage();
            $this->isProcessing = false;
            Log::error('M-Pesa payment initiation failed: ' . $e->getMessage(), [
                'invoice_id' => $this->invoice->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    protected function createInstallmentPlan()
    {
        // This is a simplified version. In a real app, you'd create a payment plan record
        // and schedule the installments with their respective due dates
        Log::info('Creating installment plan', [
            'invoice_id' => $this->invoice->id,
            'installments' => $this->installmentAmounts,
            'total_amount' => $this->amount,
            'user_id' => Auth::id()
        ]);
        
        // In a real implementation, you would create records in the payment_plans table
        // and schedule the installments with their due dates
    }
    
    protected function pollPaymentStatus($checkoutRequestId, $attempts = 0)
    {
        if ($attempts >= 30) { // 5 minutes max (10s * 30 = 300s)
            $this->paymentStatus = 'timeout';
            $this->paymentMessage = 'Payment verification timed out. Please check your M-Pesa statement and refresh the page.';
            $this->isProcessing = false;
            return;
        }
        
        // Check payment status after a delay
        $this->dispatch('check-payment-status', 
            checkoutRequestId: $checkoutRequestId,
            attempts: $attempts
        )->delay(now()->addSeconds(10));
    }
    
    public function checkPaymentStatus($checkoutRequestId, $attempts)
    {
        try {
            $mpesaService = app(MpesaService::class);
            $status = $mpesaService->checkStkStatus($checkoutRequestId);
            
            if ($status === 'pending') {
                // Continue polling
                $this->pollPaymentStatus($checkoutRequestId, $attempts + 1);
                return;
            }
            
            // Payment completed or failed
            $this->isProcessing = false;
            $this->paymentStatus = $status;
            
            if ($status === 'completed') {
                $this->paymentMessage = 'Payment received successfully! Your receipt has been sent to your email and phone.';
                $this->dispatch('payment-completed');
                
                // Emit event to refresh parent component if needed
                $this->dispatch('paymentProcessed', ['status' => 'success', 'invoice' => $this->invoice->id]);
            } else {
                $this->paymentMessage = 'Payment failed. Please try again or contact support.';
                $this->dispatch('payment-completed');
            }
            
        } catch (\Exception $e) {
            Log::error('Error checking payment status: ' . $e->getMessage());
            
            // Continue polling on error
            if ($attempts < 30) {
                $this->pollPaymentStatus($checkoutRequestId, $attempts + 1);
            } else {
                $this->paymentStatus = 'error';
                $this->paymentMessage = 'Error verifying payment. Please check your M-Pesa statement or contact support.';
                $this->isProcessing = false;
            }
        }
    }
    
    protected function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/\D/', '', $phone);
        
        // Convert to 254... format if it's a Kenyan number
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }
        
        // If it's 9 digits, assume it's missing the 254 prefix
        if (strlen($phone) === 9) {
            return '254' . $phone;
        }
        
        return $phone;
    }
    
    public function handlePaymentResponse($data)
    {
        if ($data['status'] === 'success') {
            $this->paymentStatus = 'completed';
            $this->paymentMessage = 'Payment processed successfully!';
        } else {
            $this->paymentStatus = 'failed';
            $this->paymentMessage = $data['message'] ?? 'Payment processing failed.';
        }
        
        $this->isProcessing = false;
    }

    public function render()
    {
        return view('livewire.mpesa-payment-form', [
            'invoice' => $this->invoice,
            'balance' => number_format($this->invoice->balance, 2),
            'formattedAmount' => number_format($this->amount, 2),
        ]);
    }
}
