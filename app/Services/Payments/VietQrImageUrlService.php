<?php

namespace App\Services\Payments;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class VietQrImageUrlService
{
    public function imageUrl(string $accountNumber, string $amount, string $currency, string $paymentReference): string
    {
        if (! preg_match('/^\d{6,19}$/D', $accountNumber)) {
            throw ValidationException::withMessages(['payment_account_number' => 'Số tài khoản VPBank chưa được cấu hình hợp lệ.']);
        }

        if ($currency !== 'VND') {
            throw ValidationException::withMessages(['currency' => 'QR VietQR hiện chỉ hỗ trợ thanh toán bằng VND.']);
        }

        $amount = preg_replace('/\.0{1,2}$/', '', $amount) ?? '';
        if (! preg_match('/^[1-9]\d{0,12}$/D', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Số tiền thanh toán không hợp lệ để tạo QR VietQR.']);
        }

        $addInfo = preg_replace('/[^A-Z0-9]/', '', Str::upper($paymentReference)) ?? '';
        if ($addInfo === '' || strlen($addInfo) > 50) {
            throw ValidationException::withMessages(['payment_reference' => 'Mã tham chiếu không hợp lệ để tạo QR VietQR.']);
        }

        $query = http_build_query([
            'amount' => $amount,
            'addInfo' => $addInfo,
        ], '', '&', PHP_QUERY_RFC3986);

        return "https://img.vietqr.io/image/VPB-{$accountNumber}-compact2.png?{$query}";
    }
}
