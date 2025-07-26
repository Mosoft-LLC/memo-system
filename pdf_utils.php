<?php
require_once 'config.php';

/**
 * Get list of existing PDF files for a memo
 * @param string $memo_number - The memo number to find PDFs for
 * @return array - List of PDF files with their details
 */
function getExistingMemoPDFs($memo_number) {
    $pdf_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pdf_exports' . DIRECTORY_SEPARATOR;
    $pdf_files = [];
    
    if (is_dir($pdf_dir)) {
        $files = scandir($pdf_dir);
        foreach ($files as $file) {
            if (strpos($file, 'memo_' . $memo_number . '_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                $file_path = $pdf_dir . $file;
                $pdf_files[] = [
                    'filename' => $file,
                    'size' => filesize($file_path),
                    'created' => filemtime($file_path),
                    'download_url' => 'dologin.php?op=download_pdf&file=' . urlencode($file)
                ];
            }
        }
        
        // Sort by creation time, newest first
        usort($pdf_files, function($a, $b) {
            return $b['created'] - $a['created'];
        });
    }
    
    return $pdf_files;
}

/**
 * Clean up old PDF files (optional - can be called periodically)
 * @param int $days_old - Remove PDFs older than this many days
 * @return int - Number of files removed
 */
function cleanupOldPDFs($days_old = 30) {
    $pdf_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pdf_exports' . DIRECTORY_SEPARATOR;
    $removed_count = 0;
    $cutoff_time = time() - ($days_old * 24 * 60 * 60);
    
    if (is_dir($pdf_dir)) {
        $files = scandir($pdf_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                $file_path = $pdf_dir . $file;
                if (filemtime($file_path) < $cutoff_time) {
                    if (unlink($file_path)) {
                        $removed_count++;
                    }
                }
            }
        }
    }
    
    return $removed_count;
}
?>
