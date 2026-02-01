<?php
require_once __DIR__ . "/../models/Report.php";

class ReportController {
    
    private $reportModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../../index.php");
            exit;
        }

        $this->reportModel = new Report();
    }

    public function index() {
        $start = $_GET['start_date'] ?? date('Y-m-01');
        $end = $_GET['end_date'] ?? date('Y-m-t');

        // --- Pagination Logic ---
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10; // Number of records per page
        $offset = ($page - 1) * $limit;

        // Fetch Financial Data
        $incomeData = $this->reportModel->getIncome($start, $end);
        $expenseData = $this->reportModel->getExpenses($start, $end);
        
        // Pass limit & offset to Model (You must update your Model to accept these)
        $ledger = $this->reportModel->getLedger($start, $end, $limit, $offset);
        
        // Get Total Count for Pagination Links
        // (You must create a countLedgerEntries method in your Model)
        $totalRecords = $this->reportModel->countLedgerEntries($start, $end); 
        $totalPages = ceil($totalRecords / $limit);

        $chartData = $this->reportModel->getExpenseByCategory($start, $end);

        // Calculate Summary
        $summary = [
            'income' => $incomeData['total'] ?? 0,
            'tax' => $incomeData['tax'] ?? 0,
            'expense' => $expenseData['total'] ?? 0,
            'profit' => ($incomeData['total'] ?? 0) - ($expenseData['total'] ?? 0)
        ];

        // Handle Export (Export ALL data, ignore pagination limit)
        if (isset($_GET['export']) && $_GET['export'] == 'csv') {
            $fullLedger = $this->reportModel->getLedger($start, $end, 100000, 0); // Get all
            $this->exportCSV($fullLedger, $summary, $start, $end);
        }

        // Return Data + Pagination Info
        return compact(
            'summary', 
            'ledger', 
            'chartData', 
            'start', 
            'end', 
            'page', 
            'totalPages',
            'totalRecords'
        );
    }

    private function exportCSV($ledger, $summary, $start, $end) {
        $filename = "Report_" . $start . "_" . $end . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Company Financial Report']);
        fputcsv($output, ['Period', "$start to $end"]);
        fputcsv($output, []);
        fputcsv($output, ['Total Income', $summary['income']]);
        fputcsv($output, ['Total Expenses', $summary['expense']]);
        fputcsv($output, ['Net Profit', $summary['profit']]);
        fputcsv($output, []);
        fputcsv($output, ['Date', 'Type', 'Description', 'Amount']);
        
        foreach ($ledger as $row) {
            fputcsv($output, [$row['date'], $row['type'], $row['description'], $row['amount']]);
        }
        fclose($output);
        exit;
    }
}
?>