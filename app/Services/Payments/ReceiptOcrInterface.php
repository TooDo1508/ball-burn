<?php

namespace App\Services\Payments;

interface ReceiptOcrInterface
{
    public function extract(string $imagePath): string;
}
