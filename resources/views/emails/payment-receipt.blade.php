@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        {{ config('app.name') }}
    @endcomponent
@endslot

# Payment Receipt

Hello {{ $user->name }},

Thank you for your payment. Here are the details of your transaction:

@component('mail::table')
| Description          | Details                          |
|:---------------------|:---------------------------------|
| **Receipt Number**   | {{ $receipt_number }}            |
| **Transaction Date** | {{ $transaction_date }}          |
| **Amount Paid**      | KES {{ $amount }}                |
| **Payment Method**   | M-Pesa                           |
| **Invoice Number**   | {{ $invoice->invoice_number ?? 'N/A' }} |
| **New Balance**      | KES {{ $balance }}               |
@endcomponent

## Payment Details
- **Student Name:** {{ $invoice->student->name ?? 'N/A' }}
- **Class:** {{ $invoice->classGroup->name ?? 'N/A' }}
- **Term:** {{ $invoice->term->name ?? 'N/A' }} {{ $invoice->academicYear->name ?? '' }}

If you have any questions about this payment, please contact our support team.

Thank you for choosing {{ config('app.name') }}!

{{-- Footer --}}
@slot('footer')
    @component('mail::footer')
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.\n
        @if(isset($invoice->school))
            {{ $invoice->school->name ?? '' }}\n
            @if(isset($invoice->school->address))
                {{ $invoice->school->address }}\n            @endif

            @if(isset($invoice->school->phone))
                Phone: {{ $invoice->school->phone }}\n
            @endif

            @if(isset($invoice->school->email))
                Email: {{ $invoice->school->email }}
            @endif
        @endif
    @endcomponent
@endslot
@endcomponent
