<?php
require_once '../db.php';

if (isset($_GET['tournament_id'])) {
    $tournament_id = intval($_GET['tournament_id']);
    
    $sql = "SELECT f.*, 
            t1.team_name as team1_name,
            t2.team_name as team2_name
            FROM fixtures f
            LEFT JOIN registrations t1 ON f.team1_id = t1.id
            LEFT JOIN registrations t2 ON f.team2_id = t2.id
            WHERE f.tournament_id = ?
            ORDER BY 
                CASE f.stage 
                    WHEN 'group' THEN 1
                    WHEN 'quarter-final' THEN 2
                    WHEN 'semi-final' THEN 3
                    WHEN 'final' THEN 4
                END,
                f.group_name,
                f.match_number";
                
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fixtures = [];
    while ($fixture = $result->fetch_assoc()) {
        $fixtures[] = $fixture;
    }
    
    header('Content-Type: application/json');
    echo json_encode($fixtures);
    
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No tournament ID provided']);
}
