<?php
header('Content-Type: application/json');

// Load configuration
require_once 'config.php';

// Connect to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Handle connection error
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Validate and sanitize input
$search = trim($_GET['search'] ?? '');
$gene = trim($_GET['gene'] ?? '');

if (empty($search) || empty($gene)) {
    http_response_code(400);
    echo json_encode(['error' => 'Both "search" and "gene" parameters are required']);
    exit;
}

if (strlen($search) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term must be at least 2 characters']);
    exit;
}

// Table name comes from $gene (assumes it's a valid table name)
$table = $conn->real_escape_string($gene);

// Define columns to search
$columns = ['Build','Chr','Start','End','Ref','Alt','GenetOri','Technique','Ethnicity','GeographicalOrigin','Pop','Disease','Gene','AAChange','HGVS_NM','HGVS_NC','HGVS_NP','HGVS_NC','Mutation','dbSNP','ACMG_Classification'];

// Build dynamic WHERE clause
$likeClause = implode(' OR ', array_map(fn($col) => "$col LIKE CONCAT('%', ?, '%')", $columns));
$sql = "SELECT " . implode(', ', $columns) . " 
        FROM `$table`
        WHERE ($likeClause) AND Gene = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed', 'details' => $conn->error]);
    exit;
}

// Bind parameters (search term repeated for each LIKE, plus one for Gene = ?)
$types = str_repeat('s', count($columns)) . 's';
$params = array_merge(array_fill(0, count($columns), $search), [$gene]);
$stmt->bind_param($types, ...$params);

// Execute and fetch results
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed']);
    exit;
}

$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Output JSON
echo json_encode([
    'success' => true,
    'total' => count($data),
    'data' => $data
]);

$stmt->close();
$conn->close();
?>
