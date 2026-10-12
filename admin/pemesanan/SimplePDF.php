<?php
/**
 * SimplePDF
 * Pembuat PDF sederhana tanpa library tambahan.
 * Menggunakan font bawaan PDF: Helvetica.
 */
class SimplePDF
{
    private array $objects = [];
    private string $content = '';
    private int $pageWidth = 595;
    private int $pageHeight = 842;
    private float $margin = 42;
    private float $y = 800;
    private array $images = [];

    public function __construct()
    {
        $this->content = "q\n";
    }

    private function esc(string $text): string
    {
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $text);
    }

    public function setY(float $y): void
    {
        $this->y = $y;
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 1): void
    {
        $this->content .= sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $width, $x1, $this->pageHeight - $y1, $x2, $this->pageHeight - $y2);
    }

    public function rect(float $x, float $y, float $w, float $h, float $width = 1): void
    {
        $this->content .= sprintf("%.2F w %.2F %.2F %.2F %.2F re S\n", $width, $x, $this->pageHeight - $y - $h, $w, $h);
    }

    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->content .= sprintf("BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $font, $size, $x, $this->pageHeight - $y, $this->esc($text));
    }

    public function centered(float $y, string $text, float $size = 10, bool $bold = false): void
    {
        $width = $this->textWidth($text, $size, $bold);
        $this->text(($this->pageWidth - $width) / 2, $y, $text, $size, $bold);
    }

    private function textWidth(string $text, float $size, bool $bold = false): float
    {
        $clean = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
        $factor = $bold ? 0.56 : 0.52;
        return strlen($clean) * $size * $factor;
    }

    public function labelValue(float $y, string $label, string $value): void
    {
        $this->text(55, $y, $label, 10, true);
        $this->text(175, $y, ': ' . $value, 10, false);
    }

    /**
     * Menambahkan gambar JPEG ke halaman PDF.
     * Koordinat x/y menggunakan titik dari kiri/atas, seperti method text().
     */
    public function image(string $filename, float $x, float $y, float $w, float $h): void
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return;
        }

        $info = @getimagesize($filename);
        if ($info === false || ($info[2] ?? null) !== IMAGETYPE_JPEG) {
            return;
        }

        $key = 'I' . (count($this->images) + 1);
        $this->images[$key] = [
            'data' => file_get_contents($filename),
            'width' => (int) $info[0],
            'height' => (int) $info[1],
        ];

        $pdfY = $this->pageHeight - $y - $h;
        $this->content .= sprintf(
            "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n",
            $w,
            $h,
            $x,
            $pdfY,
            $key
        );
    }

    public function output(string $filename): void
    {
        $this->content .= "Q\n";

        // Objek dasar PDF.
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';

        // Nomor object gambar dimulai setelah object font.
        $imageObjectStart = 7;
        $xObjectEntries = '';
        $i = 0;
        foreach ($this->images as $key => $image) {
            $objNo = $imageObjectStart + $i;
            $xObjectEntries .= '/' . $key . ' ' . $objNo . ' 0 R ';
            $i++;
        }

        $resources = '/Font << /F1 5 0 R /F2 6 0 R >>';
        if ($xObjectEntries !== '') {
            $resources .= ' /XObject << ' . trim($xObjectEntries) . ' >>';
        }

        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << ' . $resources . ' >> /Contents 4 0 R >>';
        $objects[] = '<< /Length ' . strlen($this->content) . " >>\nstream\n" . $this->content . "endstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        // Tambahkan object gambar JPEG langsung dengan DCTDecode.
        foreach ($this->images as $image) {
            $objects[] = '<< /Type /XObject /Subtype /Image /Width ' . $image['width']
                . ' /Height ' . $image['height']
                . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
                . strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d 65535 f \n", 0);
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}