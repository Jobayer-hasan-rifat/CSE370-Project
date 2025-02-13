<?php
require_once '../db.php';

if (isset($_GET['tournament_id'])) {
    $tournament_id = intval($_GET['tournament_id']);
    
    $stmt = $conn->prepare("SELECT id, team_name, manager_name, email, contact_number FROM registrations WHERE tournament_id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $teams = array();
    while ($team = $result->fetch_assoc()) {
        $teams[] = $team;
    }
    
    header('Content-Type: application/json');
    echo json_encode($teams);
    
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No tournament ID provided']);
}
