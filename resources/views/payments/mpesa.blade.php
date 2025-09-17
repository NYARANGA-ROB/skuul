@extends('layouts.app')

@section('title', 'M-Pesa Payment - ' . config('app.name'))

@section('content')
<div class="container py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Header with School Logo and Title -->
            <div class="bg-indigo-700 text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">M-Pesa Payment</h1>
                        <p class="text-indigo-100 mt-1">Secure payment via Safaricom M-Pesa</p>
                    </div>
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                        <img src="{{ asset('img/logo/logo.png') }}" alt="{{ config('app.name') }} Logo" class="w-12 h-12">
                    </div>
                </div>
            </div>

            <!-- Invoice Summary -->
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Invoice Summary</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-sm text-gray-500">Invoice #</p>
                        <p class="font-medium">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-sm text-gray-500">Student</p>
                        <p class="font-medium">{{ $invoice->student->user->name }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-sm text-gray-500">Outstanding Balance</p>
                        <p class="text-lg font-bold text-indigo-700">Ksh {{ number_format($invoice->balance, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="p-6">
                <div class="max-w-2xl mx-auto">
                    @livewire('mpesa-payment-form', ['invoice' => $invoice])
                </div>
            </div>

            <!-- Payment Security Info -->
            <div class="bg-blue-50 p-4 border-t">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-blue-400 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h2a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium">Secure Payment</p>
                        <p class="mt-1">Your payment is processed securely via Safaricom M-Pesa. We do not store your M-Pesa PIN or any sensitive payment information.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Information -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Need help? Contact our support at <a href="mailto:finance@example.com" class="text-indigo-600 hover:text-indigo-800">finance@example.com</a> or call <span class="font-medium">+254 700 000000</span></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Format phone number as user types
    document.addEventListener('livewire:load', function () {
        const phoneInput = document.getElementById('phone');
        
        if (phoneInput) {
            phoneInput.addEventListener('input', function (e) {
                // Remove all non-digit characters
                let phone = e.target.value.replace(/\D/g, '');
                
                // Format as Kenyan number (254XXXXXXXXX)
                if (phone.startsWith('0')) {
                    phone = '254' + phone.substring(1);
                } else if (phone.startsWith('7') || phone.startsWith('1')) {
                    phone = '254' + phone;
                }
                
                // Update input value
                e.target.value = phone;
                
                // Update Livewire property
                Livewire.emit('updatedPhone', phone);
            });
        }
    });
</script>
@endpush
@endsection
