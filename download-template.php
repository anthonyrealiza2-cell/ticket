<?php
require_once 'database.php';

// Load Composer's autoloader
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$type = $_GET['type'] ?? 'tickets';

if ($type === 'tickets') {
    try {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("TicketFlow System")
            ->setLastModifiedBy("TicketFlow System")
            ->setTitle("Ticket Import Template")
            ->setSubject("Ticket Import")
            ->setDescription("Template for importing tickets into TicketFlow system");
        
        // Set sheet title
        $sheet->setTitle('Ticket Import');
        
        // Define headers - ADDED CONTACT NUMBER
        $headers = ['ID', 'Company', 'Contact Person', 'Contact Number', 'Concern', 'Priority', 'Status', 'Assigned To', 'Date'];
        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        
        // Style for headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        // Set headers and apply style
        foreach ($headers as $index => $header) {
            $column = $columnLetters[$index];
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
            
            // Set column widths
            $width = match($index) {
                0 => 12,  // ID
                1 => 30,  // Company
                2 => 25,  // Contact
                3 => 20,  // Contact Number (NEW)
                4 => 60,  // Concern
                5 => 15,  // Priority
                6 => 18,  // Status
                7 => 25,  // Assigned To
                8 => 20,  // Date
                default => 15
            };
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        
        // Style for data rows
        $dataRowStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'DDDDDD'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ];
        
        // Get sample data for dropdowns from database
        $products = $pdo->query("SELECT product_name, version FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
        $concerns = $pdo->query("SELECT concern_name FROM concerns ORDER BY concern_name")->fetchAll(PDO::FETCH_ASSOC);
        
        // Add 100 blank rows for data entry
        for ($row = 2; $row <= 101; $row++) {
            foreach ($columnLetters as $column) {
                $sheet->setCellValue($column . $row, '');
            }
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle);
        }
        
        // Freeze header row
        $sheet->freezePane('A2');
        
        // Add autofilter
        $sheet->setAutoFilter('A1:I1');
        
        // Add validation for Concern column (E) - matches new ticket dropdown
        for ($row = 2; $row <= 101; $row++) {
            $validation = $sheet->getCell('E' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            
            // Get concern types from database for dropdown
            $concernList = [];
            foreach ($concerns as $c) {
                $concernList[] = $c['concern_name'];
            }
            $validation->setFormula1('"' . implode(',', $concernList) . '"');
            $validation->setPrompt('Select concern type');
            $validation->setError('Please select a valid concern type');
        }
        
        // Add validation for Priority column (F) - matches new ticket
        for ($row = 2; $row <= 101; $row++) {
            $validation = $sheet->getCell('F' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"Low,Medium,High"');
            $validation->setPrompt('Select priority level');
            $validation->setError('Please select a valid priority');
        }
        
        // Add validation for Status column (G)
        for ($row = 2; $row <= 101; $row++) {
            $validation = $sheet->getCell('G' . $row)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"Pending,Assigned,In Progress,Resolved,Closed"');
            $validation->setPrompt('Select status');
            $validation->setError('Please select a valid status');
        }
        
        // Create second sheet for reference data
        $spreadsheet->createSheet();
        $spreadsheet->setActiveSheetIndex(1);
        $refSheet = $spreadsheet->getActiveSheet();
        $refSheet->setTitle('Reference Data');
        
        // Style for reference sheet
        $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $refSheet->getColumnDimension('A')->setWidth(30);
        $refSheet->getColumnDimension('B')->setWidth(50);
        
        // Get all reference data
        $products = $pdo->query("SELECT product_name, version FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);
        $concerns = $pdo->query("SELECT concern_name FROM concerns ORDER BY concern_name")->fetchAll(PDO::FETCH_ASSOC);
        $techs = $pdo->query("SELECT CONCAT(firstname, ' ', lastname) as name FROM technical_staff ORDER BY firstname")->fetchAll(PDO::FETCH_ASSOC);
        
        // Title
        $refSheet->setCellValue('A1', 'REFERENCE DATA - For your information only');
        $refSheet->mergeCells('A1:B1');
        
        // Products section
        $row = 3;
        $refSheet->setCellValue('A' . $row, 'Available Products:');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($products as $p) {
            $refSheet->setCellValue('A' . $row, '•');
            $refSheet->setCellValue('B' . $row, $p['product_name'] . ' v' . $p['version']);
            $row++;
        }
        
        // Concerns section
        $row += 2;
        $refSheet->setCellValue('A' . $row, 'Concern Types (for column E):');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($concerns as $c) {
            $refSheet->setCellValue('A' . $row, '•');
            $refSheet->setCellValue('B' . $row, $c['concern_name']);
            $row++;
        }
        
        // Priority section
        $row += 2;
        $refSheet->setCellValue('A' . $row, 'Priority Options (for column F):');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $refSheet->setCellValue('A' . $row, '• Low');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Medium');
        $row++;
        $refSheet->setCellValue('A' . $row, '• High');
        
        // Status section
        $row += 2;
        $refSheet->setCellValue('A' . $row, 'Status Options (for column G):');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $refSheet->setCellValue('A' . $row, '• Pending');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Assigned');
        $row++;
        $refSheet->setCellValue('A' . $row, '• In Progress');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Resolved');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Closed');
        
        // Technical staff section
        $row += 2;
        $refSheet->setCellValue('A' . $row, 'Technical Staff (for column H):');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($techs as $t) {
            $refSheet->setCellValue('A' . $row, '•');
            $refSheet->setCellValue('B' . $row, $t['name']);
            $row++;
        }
        
        // Instructions
        $row += 2;
        $refSheet->setCellValue('A' . $row, 'Import Instructions:');
        $refSheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $refSheet->setCellValue('A' . $row, '• Leave ID blank for new tickets');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Company, Contact, and Concern are required');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Contact Number should include country code (e.g., +63 XXX XXX XXXX)');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Date format: YYYY-MM-DD HH:MM:SS');
        $row++;
        $refSheet->setCellValue('A' . $row, '• Use the dropdowns for Concern, Priority, and Status');
        
        // Set active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);
        
        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ticket_import_template.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        
        // Create writer and output to browser
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        die('Error creating Excel file: ' . $e->getMessage());
    }
}
?>