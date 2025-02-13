<?php
session_start();
require_once('../db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = $_POST['tournament_id'];
    $team_name = $_POST['team_name'];
    $manager_name = $_POST['manager_name'];
    $contact_number = $_POST['contact_number'];
    $player_names = $_POST['player_names'];
    $required_players = $_POST['required_players'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Upload team photo
        $target_dir = "../uploads/team_photos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["team_photo"]["name"], PATHINFO_EXTENSION));
        $file_name = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (!move_uploaded_file($_FILES["team_photo"]["tmp_name"], $target_file)) {
            throw new Exception("Error uploading team photo.");
        }
        
        // Check if slots are available
        $stmt = $conn->prepare("SELECT slots, registered_teams FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tournament = $result->fetch_assoc();
        
        if ($tournament['registered_teams'] >= $tournament['slots']) {
            throw new Exception("Sorry, this tournament is full!");
        }
        
        // Insert team registration
        $stmt = $conn->prepare("INSERT INTO team_registrations (tournament_id, team_name, manager_name, contact_number, team_photo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $tournament_id, $team_name, $manager_name, $contact_number, $file_name);
        $stmt->execute();
        $team_id = $conn->insert_id;
        
        // Insert player information
        $stmt = $conn->prepare("INSERT INTO team_players (team_id, player_name) VALUES (?, ?)");
        foreach ($player_names as $player_name) {
            $stmt->bind_param("is", $team_id, $player_name);
            $stmt->execute();
        }
        
        // Update tournament registered teams count
        $stmt = $conn->prepare("UPDATE tournaments SET registered_teams = registered_teams + 1 WHERE id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        
        // Insert payment record
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $payment_details = '';
        
        if ($payment_method === 'mobile') {
            $payment_details = $_POST['mobile_number'];
        } else if ($payment_method === 'card') {
            $payment_details = 'Card payment processed';
        }
        
        $stmt = $conn->prepare("INSERT INTO payments (team_id, tournament_id, payment_method, payment_details, amount) SELECT ?, ?, ?, ?, entry_fee FROM tournaments WHERE id = ?");
        $stmt->bind_param("iissi", $team_id, $tournament_id, $payment_method, $payment_details, $tournament_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Redirect to confirmation page
        $_SESSION['registration_success'] = true;
        header('Location: registration_confirmation.php?team_id=' . $team_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['registration_error'] = $e->getMessage();
        header('Location: registration.php?tournament_id=' . $tournament_id);
        exit();
    }
} else {
    header('Location: tournaments.php');
    exit();
}
?>
