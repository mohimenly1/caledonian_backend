<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال استلام رقم {{ $document->receipt_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 16px;
            color: #000;
            background: #FFF;
            width: 21cm;
            height: 29.7cm;
            margin: 0 auto;
            padding: 20px;
            direction: rtl;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        #details {
            width: 100%;
            margin-top: 20px;
        }

        .detail-section {
            margin-bottom: 20px;
        }

        .detail-section span {
            display: inline-block;
            width: 200px;
            font-weight: bold;
        }

        footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            text-align: center;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>

<header>
    <img src="{{ public_path('logo.png') }}" alt="Logo" style="width: 100px;">
    <h1>إيصال استلام رقم {{ $document->receipt_number }}</h1>
</header>

<main>
    <div id="details">
        <div class="detail-section">
            <span>طريقة الدفع كانت:</span> {{ $document->payment_method }}
        </div>
        <div class="detail-section">
            <span>استلمت من السيــد/ة:</span> {{ $document->parent->first_name }} {{ $document->parent->last_name }}
        </div>
        <div class="detail-section">
            <span>مبلغــاً وقـدره:</span> {{ number_format($document->value_received, 2) }} دينار
        </div>
        <div class="detail-section">
            <span>المصـــرف:</span> {{ $document->bank_name }}
        </div>
        <div class="detail-section">
            <span>رقم الحساب:</span> {{ $document->account_number }}
        </div>
        <div class="detail-section">
            <span>الفرع:</span> {{ $document->branch_name }}
        </div>
        <div class="detail-section">
            <span>القيمة:</span> {{ number_format($document->final_amount, 2) }} دينار
        </div>
    </div>
</main>

<footer>
    التوقيع: .................................
</footer>

</body>
</html>
