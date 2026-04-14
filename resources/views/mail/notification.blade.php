@extends('mail/layouts/mail_layout')

@section('content')
    <tr>
        <td colspan="2">
            <div style="padding: 15px 20px 35px 20px; text-align: center;">
                <span style="font-size: 20px; color: #000; font-weight: bold; margin-bottom: 10px; display: inline-block">
                    Kedves {{ $contact->name }}!
                </span>
                <br>
                <span style="font-size: 16px; color: #171717; font-weight: 600; line-height: 1.2;">
                    {{ $mailData['success_res'] ?? '' }}
                </span>

                <div style="margin-top: 15px; margin-bottom: 10px;">
                    <span>
                        <b>Név:</b> {{ $contact->name }}
                    </span>
                    <br>
                    @if($contact->email)
                        <span>
                            <b>Email:</b> {{ $contact->email }}
                        </span>
                        <br>
                    @endif
                    @if($contact->phone)
                        <span>
                            <b>Telefonszám:</b> {{ $contact->phone }}
                        </span>
                        <br>
                    @endif
                </div>

                <div style="text-align: left; font-size: 15px; margin-top: 15px;">
                    {!! $mailData['desc'] ?? '' !!}
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <td colspan="100%">
            <div style="text-align: center">
                <p style="margin: 0 0 0px; font-weight: 600; font-size: 15px">Üdvözlettel,</p>
                <p style="margin: 0 0 10px; line-height: 1.2; font-size: 14px; font-style: italic;">
                    {{ config('app.shop_name') }}
                </p>
            </div>
        </td>
    </tr>
@endsection
