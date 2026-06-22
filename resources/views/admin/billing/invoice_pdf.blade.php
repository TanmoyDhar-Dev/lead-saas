<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoiceNo }}</title>
    <style>
        /* A4 Page Optimization */
        @page { margin: 0.8in; }
        
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 16px;
            color: #1e293b; 
            line-height: 1.6;
        }
        .box-title { font-size: 13px; }

        /* Brand Colors */
        .brand-indigo { color: #4257C3; }
        .brand-cyan { color: #29B6F6; }
        .text-slate { color: #64748b; }

        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .font-black { font-weight: 900; }
        .font-bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        .tracking-widest { letter-spacing: 0.12em; }

        /* Structural Elements */
        .header { border-bottom: 3px solid #f1f5f9; padding-bottom: 25px; margin-bottom: 40px; }
        
        .info-box { width: 45%; vertical-align: top; }
        .box-title { 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            color: #4257C3; 
            margin-bottom: 12px; 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 6px; 
        }
        
        table.items-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th { 
            background: #f8fafc; 
            color: #1e293b; 
            font-size: 11px; 
            font-weight: 900; 
            text-transform: uppercase; 
            padding: 18px 15px; 
            text-align: left; 
            border-bottom: 3px solid #4257C3; 
        }
        td { padding: 22px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .total-section { float: right; width: 280px; margin-top: 50px; }
        .grand-total { 
            font-size: 22px; 
            font-weight: 900; 
            color: #1e293b; 
            padding-top: 20px; 
            border-top: 4px solid #4257C3; 
        }
        
        .status-badge { 
            background: #EEF2FF; 
            color: #4257C3; 
            padding: 8px 18px; 
            border-radius: 8px; 
            font-size: 11px; 
            font-weight: 900; 
            border: 1px solid #D1D5DB; 
        }

        .footer { 
            position: fixed; 
            bottom: 0.5in; 
            width: 100%; 
            text-align: center; 
            color: #94a3b8; 
            font-size: 11px; 
            font-weight: 900; 
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="w-full">
            <tr>
                <td>
                    @if(file_exists(public_path('logo.png')))
                        <img src="{{ public_path('logo.png') }}" style="height: 60px; width: auto; margin-bottom: 12px;">
                    @else
                        <div style="font-size: 28px; font-weight: 900; color: #4257C3; margin-bottom: 12px; letter-spacing: -1px;">
                            Lead<span style="color: #29B6F6;">Flow</span>
                        </div>
                    @endif
                    <div style="font-size: 11px; margin-left: 2px;" class="font-black uppercase tracking-widest brand-indigo">
                        Intelligence <span class="brand-cyan">&bull;</span> Extraction <span class="brand-cyan">&bull;</span> Growth
                    </div>
                </td>
                <td class="text-right">
                    <div class="uppercase font-black tracking-widest" style="font-size: 11px; color: #94a3b8;">Document ID</div>
                    <div class="font-black" style="font-size: 20px;">{{ $invoiceNo }}</div>
                    <div style="margin-top: 15px;"><span class="status-badge uppercase">{{ $payment->status }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="w-full" style="margin-bottom: 60px;">
        <tr>
            <td class="info-box">
                <div class="box-title">Vendor</div>
                <div class="font-black" style="font-size: 16px;">{{ $company['name'] }}</div>
                <div class="font-bold text-slate">{{ $company['email'] }}</div>
            </td>
            <td style="width: 10%;"></td>
            <td class="info-box">
                <div class="box-title">Bill To</div>
                <div class="font-black" style="font-size: 16px;">{{ $payment->user->name }}</div>
                <div class="font-bold text-slate">{{ $payment->user->email }}</div>
                <div style="margin-top: 15px;">
                    <span class="uppercase font-black" style="font-size: 10px; color: #94a3b8;">Activation Date:</span>
                    <span class="font-black brand-indigo" style="font-size: 14px;">{{ $payment->created_at->format('M d, Y') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr class="uppercase font-black tracking-widest">
                <th style="width: 45%;">Service Access</th>
                <th>Gateway</th>
                <th>Validity Until</th>
                <th class="text-right">Price (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="font-black" style="font-size: 15px; color: #1e293b;">SaaS Subscription Access</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 6px; font-weight: bold;">Lead Intelligence Discovery Rights</div>
                </td>
                <td class="font-bold text-slate">{{ $payment->gateway }}</td>
                @php
                    $parts = explode('|', $payment->duration_note ?? '');
                    $targetDate = $parts[0] ?: 'Subscription Update';
                    $targetDate = str_replace('Set to ', '', $targetDate);
                @endphp
                <td class="font-black brand-indigo" style="font-size: 15px;">{{ $targetDate }}</td>
                <td class="text-right font-black" style="font-size: 18px;">${{ number_format($payment->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <table class="w-full">
            <tr style="color: #94a3b8;" class="uppercase font-black">
                <td style="border:none; font-size: 11px; padding-bottom: 8px;">Subtotal</td>
                <td class="text-right" style="border:none;">${{ number_format($payment->amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td style="border:none; text-transform: uppercase; letter-spacing: 2px;">Total Payable</td>
                <td class="text-right brand-indigo" style="border:none;">${{ number_format($payment->amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $company['name'] }} &bull; Intelligence Engine &bull; {{ $company['email'] }}
    </div>

</body>
</html>
