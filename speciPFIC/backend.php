<?php
header('Content-Type: application/json');

// Load configuration
require_once 'config.php'; // Adjust to 'config_newdb.php' if needed

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
    echo json_encode(['error' => 'Both search and gene parameters are required']);
    exit;
}

if (strlen($search) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term must be at least 2 characters']);
    exit;
}

$table = strtolower($gene);

// Define columns to search (adjust based on actual schema if needed)
$columns = ['Build', 'Chr', 'Start', 'End', 'Ref', 'Alt', 'References_info', 
'GenetOri', 'Technique', 'Ethnicity', 'GeographicalOrigin', 'Pop', 'Disease',
 'Gene', 'AAChange', 'HGVS_NM', 'HGVS_NC', 'HGVS_NP', 'Mutation', 'Mutationeffect',
  'dbSNP', 'Inheritance', 'ACMG_Classification', 'variant_one', 'variant_two'];

// Build dynamic LIKE clause
$likeClause = implode(' OR ', array_map(fn($col) => "$col LIKE CONCAT('%', ?, '%')", $columns));

// Final SQL query
$sql = "SELECT " . implode(', ', $columns) . " FROM `$table` 
        WHERE ($likeClause) AND Gene = ?";

// Prepare statement
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed']);
    exit;
}

// Bind parameters
$types = str_repeat('s', count($columns)) . 's'; // +1 for Gene at the end
$params = array_fill(0, count($columns), $search);
$params[] = $gene;

$stmt->bind_param($types, ...$params);

// Execute query
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Query execution failed']);
    exit;
}

// Fetch results
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// Return response
echo json_encode([
    'success' => true,
    'total' => count($data),
    'data' => $data
]);

$stmt->close();
$conn->close();
?>
