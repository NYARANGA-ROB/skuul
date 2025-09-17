<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Make M-Pesa Payment</h2>
                <p class="text-gray-600">Complete your fee payment securely via M-Pesa</p>
                
                @if(session('success'))
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Invoice Summary -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Invoice Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Invoice #{{ $invoice->id }}</p>
                        <p class="font-medium">{{ $invoice->fee->name ?? 'School Fees' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Outstanding Balance</p>
                        <p class="text-2xl font-bold text-indigo-600">KES {{ number_format($invoice->balance, 2) }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <form wire:submit.prevent="initiatePayment">
                <!-- Payment Status Messages -->
                @if($paymentStatus === 'pending')
                    <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h.01a1 1 0 100-2H10V9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    {{ $paymentMessage }}
                                    <div class="mt-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full animate-pulse" style="width: 100%"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Waiting for payment confirmation...</p>
                                    </div>
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif($paymentStatus === 'completed')
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ $paymentMessage }}</p>
                                @if($receipt)
                                    <div class="mt-2">
                                        <a href="{{ $receipt }}" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Download Receipt
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($paymentStatus === 'failed' || $paymentStatus === 'error' || $paymentStatus === 'timeout')
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414-1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ $paymentMessage }}</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(!in_array($paymentStatus, ['completed', 'pending']))
                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                        <div class="px-4 py-5 sm:p-6">
                            <!-- Phone Number -->
                            <div class="mb-4">
                                <label for="phoneNumber" class="block text-sm font-medium text-gray-700">M-Pesa Phone Number</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">+254</span>
                                    </div>
                                    <input type="tel" 
                                           wire:model.defer="phoneNumber" 
                                           id="phoneNumber" 
                                           name="phoneNumber" 
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-16 sm:text-sm border-gray-300 rounded-md" 
                                           placeholder="712 345 678"
                                           required>
                                </div>
                                @error('phoneNumber')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Enter your M-Pesa registered phone number</p>
                            </div>
                            
                            <!-- Amount -->
                            <div class="mb-4">
                                <label for="amount" class="block text-sm font-medium text-gray-700">Amount to Pay (KES)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">KES</span>
                                    </div>
                                    <input type="number" 
                                           wire:model.debounce.500ms="amount" 
                                           id="amount" 
                                           name="amount" 
                                           min="1" 
                                           :max="$invoice->balance"
                                           step="1"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-16 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                           placeholder="0.00"
                                           required>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm" id="price-currency">.00</span>
                                    </div>
                                </div>
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">
                                    Invoice balance: KES {{ number_format($invoice->balance, 2) }}
                                    @if($amount > $invoice->balance)
                                        <span class="text-red-600">(Amount exceeds balance)</span>
                                    @endif
                                </p>
                            </div>
                            
                            <!-- Payment Plan -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700">Payment Plan</label>
                                    <button type="button" 
                                            wire:click="toggleInstallmentForm" 
                                            class="text-sm text-indigo-600 hover:text-indigo-500 focus:outline-none">
                                        {{ $showInstallmentForm ? 'Hide Installment Options' : 'Pay in Installments' }}
                                    </button>
                                </div>
                                
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input id="full-payment" 
                                               name="paymentPlan" 
                                               type="radio" 
                                               wire:model.defer="paymentPlan"
                                               value="full"
                                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                        <label for="full-payment" class="ml-3 block text-sm font-medium text-gray-700">
                                            Full Payment (KES {{ number_format($amount, 2) }})
                                        </label>
                                    </div>
                                    
                                    @if($showInstallmentForm)
                                        <div class="ml-6 pl-4 border-l-2 border-gray-200">
                                            <div class="flex items-center">
                                                <input id="installment-payment" 
                                                       name="paymentPlan" 
                                                       type="radio" 
                                                       wire:model.defer="paymentPlan"
                                                       value="installment"
                                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                                <label for="installment-payment" class="ml-3 block text-sm font-medium text-gray-700">
                                                    Pay in Installments
                                                </label>
                                            </div>
                                            
                                            @if($paymentPlan === 'installment')
                                                <div class="mt-2 pl-7">
                                                    <div class="mb-3">
                                                        <label for="installmentCount" class="block text-sm font-medium text-gray-700">Number of Installments</label>
                                                        <select id="installmentCount" 
                                                                wire:model.debounce.500ms="installmentCount"
                                                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                            @for($i = 2; $i <= 12; $i++)
                                                                <option value="{{ $i }}">{{ $i }} {{ str_plural('Month', $i) }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <p class="text-sm font-medium text-gray-700 mb-2">Installment Schedule</p>
                                                        <div class="bg-gray-50 p-3 rounded-md max-h-60 overflow-y-auto">
                                                            @foreach($installmentAmounts as $index => $installment)
                                                                <div class="mb-2 last:mb-0">
                                                                    <label class="block text-sm text-gray-700">
                                                                        Installment {{ $index + 1 }}:
                                                                    </label>
                                                                    <div class="mt-1 flex rounded-md shadow-sm
                                                                        @error('installmentAmounts.'.$index) border-red-300 @enderror">
                                                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                                                            KES
                                                                        </span>
                                                                        <input type="number" 
                                                                               wire:model.debounce.500ms="installmentAmounts.{{ $index }}" 
                                                                               class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                                                               min="1" 
                                                                               step="0.01">
                                                                    </div>
                                                                    @error('installmentAmounts.'.$index)
                                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                                    @enderror
                                                                </div>
                                                            @endforeach
                                                            
                                                            <div class="mt-3 pt-3 border-t border-gray-200">
                                                                <div class="flex justify-between text-sm font-medium">
                                                                    <span>Total:</span>
                                                                    <span>KES {{ number_format(array_sum($installmentAmounts), 2) }}</span>
                                                                </div>
                                                                @if(abs(array_sum($installmentAmounts) - $amount) > 0.01)
                                                                    <p class="text-xs text-red-600 mt-1">
                                                                        The total installment amount (KES {{ number_format(array_sum($installmentAmounts), 2) }}) does not match the payment amount (KES {{ number_format($amount, 2) }}).
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mt-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="terms" 
                                               name="terms" 
                                               type="checkbox" 
                                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                               required>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="terms" class="font-medium text-gray-700">I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">terms and conditions</a></label>
                                        <p class="text-gray-500">By proceeding, you authorize us to process your payment via M-Pesa.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="mt-6">
                                <button type="submit" 
                                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                        @if($isProcessing) disabled @endif>
                                    @if($isProcessing)
                                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    @else
                                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18m-6-5v5m-6 0v5m-5-10v10a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h1a1 1 0 011 1z" />
                                        </svg>
                                        Pay KES {{ number_format($amount, 2) }} with M-Pesa
                                    @endif
                                </button>
                            </div>
                            
                            <!-- M-Pesa Instructions -->
                            <div class="mt-4 text-center">
                                <p class="text-xs text-gray-500">
                                    You will receive an M-Pesa prompt on your phone to complete the payment.
                                </p>
                                <div class="mt-2 flex items-center justify-center space-x-4">
                                    <img src="{{ asset('img/mpesa-logo.png') }}" alt="M-Pesa" class="h-6">
                                    <span class="text-xs text-gray-400">Secure Payment</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>
    
    <!-- JavaScript for Payment Status Polling -->
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('check-payment-status', (data) => {
                setTimeout(() => {
                    @this.checkPaymentStatus(data.checkoutRequestId, data.attempts);
                }, 100);
            });
            
            // Auto-format phone number
            const phoneInput = document.getElementById('phoneNumber');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        // If it starts with 0, keep it as is (user is typing a local number)
                        if (value.startsWith('0')) {
                            value = value.substring(0, 10); // Limit to 10 digits (0XXXXXXXXX)
                        } 
                        // If it starts with 254, keep it as is
                        else if (value.startsWith('254')) {
                            value = value.substring(0, 12); // Limit to 12 digits (254XXXXXXXXX)
                        }
                        // If it starts with 7, assume it's a local number without the leading 0
                        else if (value.startsWith('7') && value.length <= 9) {
                            value = value.substring(0, 9); // Limit to 9 digits (7XXXXXXXX)
                        }
                        // If it's a different length, truncate to 9 digits
                        else if (value.length > 9) {
                            value = value.substring(0, 9);
                        }
                    }
                    e.target.value = value;
                });
            }
            
            // Auto-format amount
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('blur', function(e) {
                    let value = parseFloat(e.target.value);
                    if (!isNaN(value)) {
                        e.target.value = value.toFixed(2);
                    }
                });
            }
        });
    </script>
</div>
