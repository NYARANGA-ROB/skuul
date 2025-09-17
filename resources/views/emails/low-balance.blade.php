@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        {{ config('app.name') }}
    @endcomponent
@endslot

# Low Balance Alert

Hello {{ $user->name }},

This is a notification that your account balance is currently low.

@component('mail::panel')
**Current Balance:** KES {{ $balance }}

**Date:** {{ $date }}
@endcomponent

## What this means:
- You're running low on your account balance
- Some features may become limited if your balance reaches zero
- We recommend topping up your account to avoid any service interruptions

## How to add funds:
1. Log in to your account at [{{ config('app.url') }}]({{ config('app.url') }})
2. Go to the Billing section
3. Select "Add Funds" and follow the instructions

If you have any questions or need assistance, please don't hesitate to contact our support team.

Thank you,
{{ config('app.name') }} Team

{{-- Footer --}}
@slot('footer')
    @component('mail::footer')
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.\n        \n        This is an automated message. Please do not reply to this email.
    @endcomponent
@endslot
@endcomponent
