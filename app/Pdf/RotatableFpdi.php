<?php

namespace App\Pdf;

use setasign\Fpdi\Fpdi;

class RotatableFpdi extends Fpdi
{
    public function rotatedImage(
        string $file,
        float $x,
        float $y,
        float $width,
        float $height,
        float $angle = 0,
    ): void {
        if (abs($angle) < 0.01) {
            $this->Image($file, $x, $y, $width, $height);

            return;
        }

        // CSS memakai sudut positif searah jarum jam, sedangkan koordinat PDF
        // memakai arah sebaliknya karena sumbu Y-nya mengarah ke atas.
        $radians = deg2rad(-$angle);
        $cosine = cos($radians);
        $sine = sin($radians);
        $originX = ($x + ($width / 2)) * $this->k;
        $originY = ($this->h - ($y + ($height / 2))) * $this->k;

        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
            $cosine,
            $sine,
            -$sine,
            $cosine,
            $originX,
            $originY,
            -$originX,
            -$originY,
        ));

        $this->Image($file, $x, $y, $width, $height);
        $this->_out('Q');
    }
}
