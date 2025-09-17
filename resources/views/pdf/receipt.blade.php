<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt - {{ $payment->mpesa_receipt_number ?? $payment->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a6da7;
            padding-bottom: 10px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .receipt-details {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .receipt-details th, .receipt-details td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .receipt-details th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            opacity: 0.1;
            pointer-events: none;
            z-index: -1;
            white-space: nowrap;
            font-weight: bold;
            color: #ccc;
        }
    </style>
</head>
<body>
    @php
        $school = $invoice->school ?? null;
        $student = $invoice->student ?? $payment->user;
    @endphp

    @if($school && $school->logo_path)
        <div style="text-align: center;">
            <img src="{{ storage_path('app/public/' . $school->logo_path) }}" class="logo" alt="School Logo">
        </div>
    @endif

    <div class="header">
        <h1 class="school-name">{{ $school->name ?? 'Skuul School Management' }}</h1>
        <div>{{ $school->address ?? 'Nairobi, Kenya' }}</div>
        <div>Tel: {{ $school->phone ?? '+254 700 000000' }} | Email: {{ $school->email ?? 'info@skuul.com' }}</div>
    </div>

    <h2 class="receipt-title">Payment Receipt</h2>

    <table class="receipt-details">
        <tr>
            <th>Receipt No:</th>
            <td colspan="3">{{ $payment->mpesa_receipt_number ?? $payment->id }}</td>
        </tr>
        <tr>
            <th>Date:</th>
            <td>{{ $payment->transaction_date ? $payment->transaction_date->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</td>
            <th>Payment Method:</th>
            <td>M-Pesa</td>
        </tr>
        <tr>
            <th>Student Name:</th>
            <td>{{ $student->name }}</td>
            <th>Admission No:</th>
            <td>{{ $student->admission_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th>Class:</th>
            <td>{{ $invoice->classGroup->name ?? 'N/A' }}</td>
            <th>Term:</th>
            <td>{{ $invoice->term->name ?? 'N/A' }} {{ $invoice->academicYear->name ?? '' }}</td>
        </tr>
    </table>

    <h3>Payment Details</h3>
    <table class="receipt-details">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Amount (KES)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Payment for {{ $invoice->description ?? 'School Fees' }}</td>
                <td class="amount">{{ number_format($payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Total Paid</strong></td>
                <td class="amount"><strong>{{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
            @if($invoice)
            <tr>
                <td>Previous Balance</td>
                <td class="amount">{{ number_format($invoice->balance + $payment->amount, 2) }}</td>
            </tr>
            <tr>
                <td><strong>New Balance</strong></td>
                <td class="amount"><strong>{{ number_format($invoice->balance, 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>

    @if($payment->paymentPlan)
    <h3>Payment Plan</h3>
    <table class="receipt-details">
        <tr>
            <th>Plan Type:</th>
            <td>{{ class_basename($payment->paymentPlan) }}</td>
            <th>Installments:</th>
            <td>{{ $payment->paymentPlan->installment_count ?? 'N/A' }}</td>
        </tr>
        @if($payment->paymentPlan->discount_percentage ?? false)
        <tr>
            <th>Discount:</th>
            <td colspan="3">{{ $payment->paymentPlan->discount_percentage }}%</td>
        </tr>
        @endif
    </table>
    @endif

    <div class="footer">
        <p>Thank you for your payment. This is an official receipt for the transaction.</p>
        <p>For any inquiries, please contact the school office.</p>
        <p>Generated on: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="watermark">
        {{ $school->name ?? 'SKUUL' }}
    </div>
</body>
</html>
