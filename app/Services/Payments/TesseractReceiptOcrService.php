<?php

namespace App\Services\Payments;

use Symfony\Component\Process\Process;

class TesseractReceiptOcrService implements ReceiptOcrInterface
{
    public function extract(string $imagePath): string
    {
        $process = new Process([
            'tesseract',
            $imagePath,
            '-',
            '-l',
            'vie+eng',
            '--psm',
            '6',
            '-c',
            'preserve_interword_spaces=1',
        ]);
        $process->setTimeout(40);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
