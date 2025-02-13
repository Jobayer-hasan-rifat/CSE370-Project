<?php
require_once '../db.php';

function generateFixtures($tournament_id) {
    global $conn;
    
    // Get registered teams
    $stmt = $conn->prepare("SELECT id, team_name FROM registrations WHERE tournament_id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
    $stmt->close();
    
    $team_count = count($teams);
    
    // Check if number of teams is even
    if ($team_count % 2 != 0) {
        return [
            'success' => false,
            'message' => 'Odd number of teams detected. Please drop a team to proceed with fixture generation.'
        ];
    }
    
    // Check minimum teams
    if ($team_count < 4) {
        return [
            'success' => false,
            'message' => 'Minimum 4 teams required for tournament.'
        ];
    }
    
    // Shuffle teams for random matchups
    shuffle($teams);
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Clear existing fixtures for this tournament
        $clear_stmt = $conn->prepare("DELETE FROM fixtures WHERE tournament_id = ?");
        $clear_stmt->bind_param("i", $tournament_id);
        $clear_stmt->execute();
        
        // Get tournament details for scheduling
        $tourn_stmt = $conn->prepare("SELECT tournament_date FROM tournaments WHERE id = ?");
        $tourn_stmt->bind_param("i", $tournament_id);
        $tourn_stmt->execute();
        $tournament = $tourn_stmt->get_result()->fetch_assoc();
        $tournament_date = new DateTime($tournament['tournament_date']);
        
        $fixtures = [];
        $match_date = clone $tournament_date;
        
        if ($team_count == 4) {
            // Semi-finals
            for ($i = 0; $i < 4; $i += 2) {
                $stmt = $conn->prepare("INSERT INTO fixtures (tournament_id, team1_id, team2_id, match_date, status) VALUES (?, ?, ?, ?, 'pending')");
                $match_date_str = $match_date->format('Y-m-d H:i:s');
                $stmt->bind_param("iiis", $tournament_id, $teams[$i]['id'], $teams[$i+1]['id'], $match_date_str);
                $stmt->execute();
                $match_date->modify('+2 hours');
            }
            
            // Finals will be scheduled after semi-finals
            $match_date->modify('+1 day');
            
        } elseif ($team_count == 8) {
            // Quarter-finals
            for ($i = 0; $i < 8; $i += 2) {
                $stmt = $conn->prepare("INSERT INTO fixtures (tournament_id, team1_id, team2_id, match_date, status) VALUES (?, ?, ?, ?, 'pending')");
                $match_date_str = $match_date->format('Y-m-d H:i:s');
                $stmt->bind_param("iiis", $tournament_id, $teams[$i]['id'], $teams[$i+1]['id'], $match_date_str);
                $stmt->execute();
                $match_date->modify('+2 hours');
            }
            
            // Semi-finals will be scheduled next day
            $match_date->modify('+1 day');
            
        } else {
            // Group stage for more than 8 teams
            $groups = array_chunk($teams, ceil($team_count / 2));
            
            foreach ($groups as $group_index => $group) {
                for ($i = 0; $i < count($group); $i++) {
                    for ($j = $i + 1; $j < count($group); $j++) {
                        $stmt = $conn->prepare("INSERT INTO fixtures (tournament_id, team1_id, team2_id, match_date, status) VALUES (?, ?, ?, ?, 'pending')");
                        $match_date_str = $match_date->format('Y-m-d H:i:s');
                        $stmt->bind_param("iiis", $tournament_id, $group[$i]['id'], $group[$j]['id'], $match_date_str);
                        $stmt->execute();
                        $match_date->modify('+2 hours');
                    }
                }
                // Next group starts next day
                $match_date->modify('+1 day');
            }
        }
        
        $conn->commit();
        return [
            'success' => true,
            'message' => 'Fixtures generated successfully!'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Error generating fixtures: ' . $e->getMessage()
        ];
    }
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = $_POST['tournament_id'] ?? null;
    
    if (!$tournament_id) {
        echo json_encode(['success' => false, 'message' => 'Tournament ID is required']);
        exit;
    }
    
    $result = generateFixtures($tournament_id);
    echo json_encode($result);
    exit;
}
?>
