<?php

use App\Pdf\RotatableFpdi;

it('writes a rotation transformation around a placed image', function () {
    $image = tempnam(sys_get_temp_dir(), 'stamp_').'.png';
    file_put_contents($image, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
    ));

    try {
        $pdf = new RotatableFpdi;
        $pdf->SetCompression(false);
        $pdf->AddPage();
        $pdf->rotatedImage($image, 20, 30, 40, 20, 90);

        $output = $pdf->Output('S');

        expect($output)
            ->toContain('q 0.00000 -1.00000 1.00000 0.00000')
            // (20 + 40/2, page height - (30 + 20/2)), converted to PDF points.
            ->toContain('113.39 728.50 cm 1 0 0 1 -113.39 -728.50 cm')
            ->toContain("\nQ\n");
    } finally {
        @unlink($image);
    }
});
