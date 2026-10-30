<?php
require_once 'config.php';

// API endpoint to fetch user's generation history
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

if (!$userId) {
    json_error_response('User not authenticated');
}

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
$offset = ($page - 1) * $limit;

try {
    // Fetch generations with pagination
    $stmt = $conn->prepare("SELECT g.*, u.username FROM generations g JOIN users u ON g.user_id = u.id WHERE g.user_id = ? ORDER BY g.created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $generations = [];
    while ($row = $result->fetch_assoc()) {
        $generations[] = [
            'id' => $row['id'],
            'prompt' => $row['prompt'],
            'model' => $row['model'],
            'image_url' => $row['image_url'],
            'width' => $row['width'],
            'height' => $row['height'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM generations WHERE user_id = ?");
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    // Get today's usage
    $usageStmt = $conn->prepare("SELECT generation_count FROM rate_limits WHERE user_id = ? AND DATE(last_generation) = CURDATE()");
    $usageStmt->bind_param("i", $userId);
    $usageStmt->execute();
    $usageResult = $usageStmt->get_result();
    $todayUsage = $usageResult->fetch_assoc()['generation_count'] ?? 0;
    
    json_success_response([
        'generations' => $generations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ],
        'usage' => [
            'today' => intval($todayUsage),
            'limit' => 50,
            'remaining' => max(0, 50 - intval($todayUsage))
        ]
    ]);
    
} catch (Exception $e) {
    debug_log('Error fetching generations: ' . $e->getMessage());
    json_error_response('Failed to fetch generation history');
}
