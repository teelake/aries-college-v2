<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Fetch analytics data
    $totalApplicants = $conn->query("SELECT COUNT(*) FROM applications")->fetch_row()[0];
    $admitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='admitted'")->fetch_row()[0];
    $notAdmitted = $conn->query("SELECT COUNT(*) FROM applications WHERE application_status='not_admitted'")->fetch_row()[0];
    $totalPayments = $conn->query("SELECT IFNULL(SUM(amount),0) FROM transactions WHERE status='success'")->fetch_row()[0];
    $messages = $conn->query("SELECT COUNT(*) FROM contact_messages")->fetch_row()[0];
    
    // Fetch recent applicants
    $recentApplicants = $conn->query("SELECT full_name, email, program_applied, created_at FROM applications ORDER BY created_at DESC LIMIT 5");
    $recentData = [];
    while($row = $recentApplicants->fetch_assoc()) {
        $recentData[] = $row;
    }
    
    // Fetch program data for chart
    $programData = $conn->query("SELECT program_applied, COUNT(*) as total FROM applications GROUP BY program_applied");
    $programLabels = [];
    $programCounts = [];
    while($row = $programData->fetch_assoc()) {
        $programLabels[] = $row['program_applied'];
        $programCounts[] = $row['total'];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'totalApplicants' => (int)$totalApplicants,
            'admitted' => (int)$admitted,
            'notAdmitted' => (int)$notAdmitted,
            'totalPayments' => (float)$totalPayments,
            'messages' => (int)$messages,
            'recentApplicants' => $recentData,
            'programLabels' => $programLabels,
            'programCounts' => $programCounts
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
