<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>QR Kód Nyomtatás - {{ $user->name }}</title>
    <style>
        @page {
            size: A5;
            margin: 0;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            box-sizing: border-box;
        }
        .qr-container {
            width: 80%;
            max-width: 300px;
            margin-bottom: 30px;
        }
        .qr-container svg {
            width: 100%;
            height: auto;
        }
        .text {
            font-size: 18px;
            line-height: 1.5;
            color: #333;
            max-width: 90%;
            word-wrap: break-word;
        }
        .user-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="user-name">{{ $user->name }}</div>
    <div class="qr-container">
        {!! $qrCode !!}
    </div>
    <div class="text">
        {!! nl2br(e($flipCitySettings['profile_qr_print_text'])) !!}
    </div>

    <div class="no-print" style="margin-top: 50px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Nyomtatás</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Bezárás</button>
    </div>

    <script>
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
