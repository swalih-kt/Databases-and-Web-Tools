<?php
header('Content-Type: application/json');

// Load configuration
require_once 'config_newdb.php';

// Connect to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Handle connection error
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Validate and sanitize input
if (!isset($_GET['search']) || empty(trim($_GET['search']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Search parameter is required']);
    exit;
}

$searchTerm = trim($_GET['search']);
if (strlen($searchTerm) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Search term must be at least 2 characters']);
    exit;
}

// Define columns to search
$columns = [
    'Build', 'Chr', 'Start','Ref', 'Alt', 'References_info',
    'GenetOri', 'Technique', 'Ethnicity', 'GeographicalOrigin', 'Pop',
    'Disease', 'refGene', 'AAChange', 'HGVS_NM', 'HGVS_NC', 'HGVS_NP',
    'Mutation', 'dbSNP', 'MutationEffect','Inheritance', 'ACMG_Classification',
    'Variant_one', 'Variant_two'
];

$table = 'final_hlcs_input'; // Your table name

// Build dynamic query string
$likeClause = implode(' OR ', array_map(fn($col) => "$col LIKE CONCAT('%', ?, '%')", $columns));
$sql = "SELECT " . implode(', ', $columns) . " FROM $table WHERE $likeClause";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation failed']);
    exit;
}

// Bind the search term for each column
$types = str_repeat('s', count($columns));
$params = array_fill(0, count($columns), $searchTerm);
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
