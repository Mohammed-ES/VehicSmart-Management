<?php
/**
 * HTML to PDF converter for VehicSmart
 * 
 * Simple PDF generator using basic PDF syntax
 */

// Define the html2pdf function
if (!function_exists('html2pdf')) {
    /**
     * Convert HTML to PDF - Simple version
     * 
     * @param string $html The HTML content to convert
     * @param string $paper_size The paper size (A4, Letter, etc.)
     * @param string $orientation The orientation (portrait, landscape)
     * @return string The PDF content
     */
    function html2pdf($html, $paper_size = 'A4', $orientation = 'portrait') {
        // Extract text content from HTML to use in the PDF
        $text = strip_tags($html);
        
        // Create a very basic PDF using PDF syntax
        $pdf = "%PDF-1.7\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 595 842] /Contents 5 0 R >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Font << /F1 6 0 R >> >>\nendobj\n";
        
        // Add content - simple text representation of the invoice
        $content = "BT\n/F1 12 Tf\n50 750 Td\n(INVOICE DOCUMENT) Tj\n0 -20 Td\n";
        
        // Add current date
        $content .= "(Generated: " . date('Y-m-d H:i:s') . ") Tj\n0 -40 Td\n";
        
        // Extract invoice number if present
        if (preg_match('/Invoice\s+#?\s*:\s*([A-Z0-9-]+)/i', $html, $matches)) {
            $content .= "(Invoice: " . $matches[1] . ") Tj\n0 -20 Td\n";
        }
        
        // Extract company name if present
        if (preg_match('/<h2[^>]*>(.*?)<\/h2>/is', $html, $matches)) {
            $content .= "(" . strip_tags($matches[1]) . ") Tj\n0 -20 Td\n";
        }
        
        // Add basic info that this is just a preview
        $content .= "(This is a simplified PDF preview. For a complete invoice, please view online.) Tj\n";
        
        $content .= "ET\n";
        
        // Compress the content
        $compressed = gzcompress($content);
        $compressed = substr($compressed, 2, -4);
        
        $pdf .= "5 0 obj\n<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n" . $compressed . "\nendstream\nendobj\n";
        $pdf .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Add xref table
        $pos = strlen($pdf);
        $pdf .= "xref\n0 7\n0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "1 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "2 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "3 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "4 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "5 0 obj"));
        $pdf .= sprintf("%010d 00000 n \n", strpos($pdf, "6 0 obj"));
        
        // Add trailer
        $pdf .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $pos . "\n%%EOF\n";
        
        return $pdf;
    }
}
