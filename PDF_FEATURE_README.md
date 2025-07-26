# PDF Generation Feature for MOSOFT MEMO SYSTEM

## Overview
The PDF generation feature automatically creates PDF versions of memos after they are sent, allowing recipients to download and archive official memo documents.

## Features

### 1. Automatic PDF Generation
- **When**: PDFs are automatically generated when a memo is successfully sent
- **Format**: Professional PDF with company branding and memo details
- **Content**: Includes full memo content, metadata, distribution list, and status information

### 2. Manual PDF Generation
- **Access**: Available through "Generate PDF" button in memo view
- **Permissions**: Only accessible to memo sender and recipients
- **Multiple Generations**: Can generate new PDFs at any time

### 3. PDF Content
Each generated PDF includes:
- **Header**: Company branding with MOSOFT MEMO SYSTEM
- **Memo Information**: Number, subject, sender details, priority, category
- **Content**: Full memo text with proper formatting
- **Distribution**: List of all recipients with read/acknowledgment status
- **Attachments**: List of any attached files (references only)
- **Footer**: Generation timestamp and document ID

### 4. Security Features
- **Access Control**: Only authorized users can generate/download PDFs
- **Secure Downloads**: PDFs served through protected download handler
- **File Protection**: PDF directory protected from direct access

## Files Added/Modified

### New Files:
1. **pdf_generator.php** - Core PDF generation functionality
2. **pdf_utils.php** - Utility functions for PDF management
3. **download_pdf.php** - Secure PDF download handler
4. **vendor/** - TCPDF library (installed via Composer)
5. **pdf_exports/** - Directory for generated PDF files

### Modified Files:
1. **functions.php** - Added PDF generation to memo sending and viewing
2. **dologin.php** - Added PDF generation and download routes
3. **images/css/site.css** - Added styling for PDF elements

## Usage Instructions

### For Senders:
1. **Automatic Generation**: Send a memo normally - PDF is generated automatically
2. **Manual Generation**: 
   - View any sent memo
   - Click "Generate PDF" button
   - Download the generated PDF

### For Recipients:
1. **View Memo**: Open any received memo
2. **Download PDF**: If PDFs exist, they will be listed in the sidebar
3. **Generate New**: Click "Generate PDF" to create a new version

### For Administrators:
1. **File Management**: PDFs stored in `pdf_exports/` directory
2. **Cleanup**: Use `cleanupOldPDFs()` function to remove old files
3. **Monitoring**: Check disk space usage regularly

## Technical Details

### Dependencies:
- **TCPDF Library**: Version 6.10.0 (installed via Composer)
- **PHP**: Version 8.0+ recommended
- **MySQL**: For memo data access

### Configuration:
- **PDF Directory**: `pdf_exports/` (auto-created)
- **File Naming**: `memo_{MEMO_NUMBER}_{TIMESTAMP}.pdf`
- **Permissions**: 755 for directories, secure download handling

### Performance Considerations:
- **File Size**: Average 50-200KB per PDF
- **Generation Time**: 1-3 seconds per PDF
- **Storage**: Consider implementing cleanup for old files

## Security Notes

1. **Access Control**: All PDF operations verify user permissions
2. **File Protection**: Direct directory access blocked
3. **Download Security**: Files served through protected handler
4. **Input Validation**: All parameters sanitized and validated

## Troubleshooting

### Common Issues:
1. **PDF Generation Failed**: Check file permissions on pdf_exports directory
2. **Download Not Working**: Verify download_pdf.php has correct permissions
3. **Memory Issues**: For large memos, may need to increase PHP memory limit

### Error Messages:
- "Access denied": User doesn't have permission for this memo
- "PDF Generation Failed": Check server logs for specific error
- "File not found": PDF may have been deleted or never generated

## Future Enhancements

Possible improvements:
1. **Bulk PDF Generation**: Generate PDFs for multiple memos
2. **Email Integration**: Automatically email PDFs to recipients
3. **Digital Signatures**: Add digital signatures to PDFs
4. **Templates**: Custom PDF templates for different memo types
5. **Archival**: Integration with document management systems

## Support

For technical support or feature requests, contact the system administrator.

---
*PDF Generation Feature - MOSOFT MEMO SYSTEM v2.0*
