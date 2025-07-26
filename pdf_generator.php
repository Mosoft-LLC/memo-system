<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

// Create database connection if not already established
if (!isset($connection) || !$connection) {
    $connection = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbname);
    if (!$connection) {
        die('Database connection failed: ' . mysqli_connect_error());
    }
}

/**
 * Generate PDF version of a memo
 * @param int $memo_id - The memo ID to generate PDF for
 * @param int $uid - The user ID requesting the PDF (for access control)
 * @return array - Returns array with 'success' boolean and 'message' or 'file_path'
 */
function generateMemoPDF($memo_id, $uid) {
    global $connection;
    
    try {
        // Clear any output buffers that might interfere with PDF generation
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Verify user has access to this memo
        $access_check = mysqli_query($connection, "
            SELECT m.*, u.first_name, u.last_name, u.position, d.department_name as sender_dept,
                   mc.category_name, mc.color as category_color,
                   ur.role_name as sender_role
            FROM memos m 
            JOIN users u ON m.sender_id = u.user_id 
            LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
            LEFT JOIN memo_categories mc ON m.category_id = mc.category_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            LEFT JOIN user_roles ur ON u.role_id = ur.role_id
            WHERE m.memo_id = $memo_id 
            AND (m.sender_id = $uid OR mr.user_id = $uid)
        ");
        
        if (mysqli_num_rows($access_check) == 0) {
            return ['success' => false, 'message' => 'Access denied or memo not found.'];
        }
        
        $memo = mysqli_fetch_assoc($access_check);
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('MOSOFT MEMO SYSTEM');
        $pdf->SetAuthor($memo['first_name'] . ' ' . $memo['last_name']);
        $pdf->SetTitle('Memo: ' . $memo['subject']);
        $pdf->SetSubject('Official Memo Document');
        $pdf->SetKeywords('Memo, Official, Document, ' . $memo['category_name']);
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'MOSOFT MEMO SYSTEM', 'Official Memo Document');
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font for main content
        $pdf->SetFont('helvetica', '', 12);
        
        // Generate priority badge color
        $priority_colors = [
            'urgent' => '#dc3545',
            'high' => '#fd7e14', 
            'normal' => '#6c757d',
            'low' => '#28a745'
        ];
        $priority_color = $priority_colors[$memo['priority']] ?? '#6c757d';
        
        // Generate memo header with enhanced styling
        $html = '
        <style>
            .memo-header {
                background-color: #f8f9fa;
                border: 2px solid #dee2e6;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
            }
            .memo-title {
                font-size: 24px;
                font-weight: bold;
                color: #212529;
                margin-bottom: 15px;
                text-align: center;
                border-bottom: 2px solid #007bff;
                padding-bottom: 10px;
            }
            .memo-meta {
                font-size: 11px;
                color: #6c757d;
                margin-bottom: 10px;
            }
            .memo-meta strong {
                color: #495057;
                font-weight: bold;
            }
            .priority-badge {
                background-color: ' . $priority_color . ';
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .category-badge {
                background-color: ' . ($memo['category_color'] ?? '#007bff') . ';
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: bold;
            }
            .confidential-badge {
                background-color: #dc3545;
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .memo-content {
                font-size: 12px;
                line-height: 1.6;
                color: #212529;
                margin-top: 20px;
                padding: 15px;
                border-left: 4px solid #007bff;
                background-color: #f8f9fa;
            }
            .memo-footer {
                margin-top: 30px;
                font-size: 10px;
                color: #6c757d;
                text-align: center;
                border-top: 1px solid #dee2e6;
                padding-top: 15px;
            }
        </style>
        
        <div class="memo-header">
            <div class="memo-title">' . htmlspecialchars($memo['subject']) . '</div>
            
            <table style="width: 100%; margin-bottom: 15px;">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        <div class="memo-meta">
                            <strong>Memo Number:</strong> ' . htmlspecialchars($memo['memo_number']) . '<br>
                            <strong>From:</strong> ' . htmlspecialchars($memo['first_name'] . ' ' . $memo['last_name']) . '<br>';
                            
        if ($memo['sender_dept']) {
            $html .= '<strong>Department:</strong> ' . htmlspecialchars($memo['sender_dept']) . '<br>';
        }
        if ($memo['sender_role']) {
            $html .= '<strong>Role:</strong> ' . htmlspecialchars($memo['sender_role']) . '<br>';
        }
        
        $html .= '
                            <strong>Date Sent:</strong> ' . date('F j, Y \a\t g:i A', strtotime($memo['created_at'])) . '
                        </div>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <div class="memo-meta">
                            <span class="priority-badge">' . ucfirst($memo['priority']) . ' Priority</span><br><br>';
                            
        if ($memo['category_name']) {
            $html .= '<span class="category-badge">' . htmlspecialchars($memo['category_name']) . '</span><br><br>';
        }
        
        if ($memo['is_confidential']) {
            $html .= '<span class="confidential-badge">🔒 Confidential</span><br><br>';
        }
        
        if ($memo['requires_acknowledgment']) {
            $html .= '<span style="background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 12px; font-size: 10px; font-weight: bold;">⚠️ Requires Acknowledgment</span><br><br>';
        }
        
        if ($memo['due_date']) {
            $html .= '<strong>Due Date:</strong> ' . date('F j, Y', strtotime($memo['due_date'])) . '<br>';
        }
        
        if ($memo['expiration_date']) {
            $html .= '<strong>Expires:</strong> ' . date('F j, Y', strtotime($memo['expiration_date'])) . '<br>';
        }
        
        $html .= '
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="memo-content">
            <h3 style="color: #007bff; margin-bottom: 15px; font-size: 14px;">Message Content:</h3>
            ' . nl2br(htmlspecialchars($memo['content'])) . '
        </div>';
        
        // Add attachments section if any exist
        $attachments_result = mysqli_query($connection, "
            SELECT * FROM memo_attachments 
            WHERE memo_id = $memo_id 
            ORDER BY created_at
        ");
        
        if (mysqli_num_rows($attachments_result) > 0) {
            $html .= '
            <div style="margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 8px;">
                <h3 style="color: #495057; margin-bottom: 15px; font-size: 14px;">📎 Attachments:</h3>
                <ul style="margin: 0; padding-left: 20px;">';
                
            while ($attachment = mysqli_fetch_assoc($attachments_result)) {
                $file_size = round($attachment['file_size'] / 1024, 2);
                $html .= '<li style="margin-bottom: 5px; font-size: 11px;">' . 
                         htmlspecialchars($attachment['original_filename']) . 
                         ' (' . $file_size . ' KB)</li>';
            }
            
            $html .= '</ul></div>';
        }
        
        // Add recipients information
        $recipients_result = mysqli_query($connection, "
            SELECT u.first_name, u.last_name, u.position, d.department_name,
                   mr.is_read, mr.read_at, mr.acknowledged_at
            FROM memo_recipients mr 
            JOIN users u ON mr.user_id = u.user_id 
            LEFT JOIN departments d ON u.department_id = d.department_id 
            WHERE mr.memo_id = $memo_id 
            ORDER BY u.last_name, u.first_name
        ");
        
        if (mysqli_num_rows($recipients_result) > 0) {
            $html .= '
            <div style="margin-top: 20px; padding: 15px; background-color: #d1ecf1; border-radius: 8px;">
                <h3 style="color: #0c5460; margin-bottom: 15px; font-size: 14px;">👥 Distribution List:</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <thead>
                        <tr style="background-color: #b8daff;">
                            <th style="border: 1px solid #84c5d9; padding: 8px; text-align: left;">Recipient</th>
                            <th style="border: 1px solid #84c5d9; padding: 8px; text-align: left;">Department</th>
                            <th style="border: 1px solid #84c5d9; padding: 8px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
            while ($recipient = mysqli_fetch_assoc($recipients_result)) {
                $status = 'Unread';
                if ($recipient['acknowledged_at']) {
                    $status = '✅ Acknowledged';
                } elseif ($recipient['is_read']) {
                    $status = '👀 Read';
                }
                
                $html .= '<tr>
                    <td style="border: 1px solid #84c5d9; padding: 6px;">' . 
                    htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) . 
                    ($recipient['position'] ? '<br><small>' . htmlspecialchars($recipient['position']) . '</small>' : '') . '</td>
                    <td style="border: 1px solid #84c5d9; padding: 6px;">' . 
                    htmlspecialchars($recipient['department_name'] ?? 'N/A') . '</td>
                    <td style="border: 1px solid #84c5d9; padding: 6px; text-align: center;">' . $status . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table></div>';
        }
        
        // Add footer
        $html .= '
        <div class="memo-footer">
            <p>Generated on ' . date('F j, Y \a\t g:i A T') . '</p>
            <p>This is an official document generated by MOSOFT MEMO SYSTEM</p>
            <p>Document ID: MEMO-PDF-' . $memo_id . '-' . time() . '</p>
        </div>';
        
        // Write the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Create PDF directory if it doesn't exist
        $pdf_dir = __DIR__ . DIRECTORY_SEPARATOR . 'pdf_exports' . DIRECTORY_SEPARATOR;
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }
        
        // Generate filename
        $filename = 'memo_' . $memo['memo_number'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $file_path = $pdf_dir . $filename;
        
        // Output PDF to file
        $pdf->Output($file_path, 'F');
        
        return [
            'success' => true, 
            'file_path' => $file_path,
            'filename' => $filename,
            'download_url' => 'dologin.php?op=download_pdf&file=' . urlencode($filename)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error generating PDF: ' . $e->getMessage()];
    }
}

/**
 * Download PDF memo (serves the file to browser)
 * @param string $filename - The PDF filename to download
 * @param int $uid - User ID for access control
 */
function downloadMemoPDF($filename, $uid) {
    // Clear any output buffers before serving file
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Sanitize filename
    $filename = basename($filename);
    $file_path = __DIR__ . DIRECTORY_SEPARATOR . 'pdf_exports' . DIRECTORY_SEPARATOR . $filename;
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('PDF file not found.');
    }
    
    // Extract memo number from filename for access control
    preg_match('/memo_([^_]+)_/', $filename, $matches);
    if (!$matches[1]) {
        http_response_code(403);
        die('Invalid PDF file.');
    }
    
    $memo_number = $matches[1];
    
    // Verify user has access to this memo
    global $connection;
    $access_check = mysqli_query($connection, "
        SELECT m.memo_id 
        FROM memos m 
        LEFT JOIN memo_recipients mr ON m.memo_id = mr.memo_id 
        WHERE m.memo_number = '$memo_number' 
        AND (m.sender_id = $uid OR mr.user_id = $uid)
    ");
    
    if (mysqli_num_rows($access_check) == 0) {
        http_response_code(403);
        die('Access denied.');
    }
    
    // Serve the file
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    readfile($file_path);
    exit();
}

/**
 * Auto-generate PDF after sending memo
 * @param int $memo_id - The memo ID that was just sent
 * @param int $sender_id - The sender's user ID
 * @return array - Result of PDF generation
 */
function autoGeneratePDFAfterSend($memo_id, $sender_id) {
    return generateMemoPDF($memo_id, $sender_id);
}
?>
