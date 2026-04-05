@component('mail::message')

<div style="text-align: center; margin-bottom: 20px;">
    <img src="https://i.ibb.co/4ncv9hfw/header-img.png" alt="SENCO Header" style="max-width: 100%; height: auto;">
</div>

# Welcome to the Team, {{ $name }}!

You have been successfully added as a **{{ $role }}** for the **SENCO 2026 Finance Committee System**.

To get started, please set your account password by clicking the button below:

@component('mail::button', ['url' => $url])
Set Up Your Account
@endcomponent

This secure link will expire in **24 hours**.

If you did not expect this invitation or need assistance, please contact the system administrator.

Thanks,<br>
{{ config('app.name') }}

@endcomponent