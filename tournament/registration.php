<?php
session_start();
require_once('../db.php');

if (isset($_GET['tournament_id'])) {
    $tournament_id = $_GET['tournament_id'];
    
    // Get tournament details
    $stmt = $conn->prepare("SELECT t.*, g.number_of_players FROM tournaments t JOIN games g ON t.game_type = g.game_name WHERE t.id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tournament = $result->fetch_assoc();
    
    if (!$tournament) {
        header('Location: tournaments.php');
        exit();
    }
    
    $required_players = $tournament['number_of_players'];
} else {
    header('Location: tournaments.php');
    exit();
}

// Get user details if logged in
$manager_name = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $manager_name = $user['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Registration - <?php echo htmlspecialchars($tournament['tournament_name']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .registration-form {
            background: rgba(7, 166, 188, 0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .payment-section {
            display: none;
        }
        
        .mobile-banking-fields,
        .card-payment-fields {
            display: none;
            margin-top: 15px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-group {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2 class="text-center mb-4">Tournament Registration</h2>
            <h4 class="text-center mb-4"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h4>
            
            <form id="registrationForm" action="process_registration.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                <input type="hidden" name="required_players" value="<?php echo $required_players; ?>">
                
                <div class="form-group">
                    <label>Team Name</label>
                    <input type="text" class="form-control" name="team_name" required>
                </div>
                
                <div class="form-group">
                    <label>Manager Name</label>
                    <input type="text" class="form-control" name="manager_name" value="<?php echo htmlspecialchars($manager_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" class="form-control" name="contact_number" required>
                </div>
                
                <div class="form-group">
                    <label>Team Photo</label>
                    <input type="file" class="form-control" name="team_photo" accept="image/*" required>
                </div>
                
                <div class="form-group">
                    <label>Players Information</label>
                    <?php for($i = 1; $i <= $required_players; $i++): ?>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Player <?php echo $i; ?></span>
                            </div>
                            <input type="text" class="form-control" name="player_names[]" required>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-primary" onclick="showPayment()">Pay Now</button>
                    <a href="tournaments.php" class="btn btn-danger">Cancel</a>
                    <a href="../index.php" class="btn btn-secondary">Home</a>
                </div>
            </form>
            
            <!-- Payment Section -->
            <div id="paymentSection" class="payment-section">
                <h3 class="text-center mb-4">Payment Details</h3>
                <p class="text-center">Entry Fee: $<?php echo number_format($tournament['entry_fee'], 2); ?></p>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select class="form-control" id="paymentMethod" onchange="togglePaymentFields()">
                        <option value="cash">Cash</option>
                        <option value="mobile">Mobile Banking</option>
                        <option value="card">Card Payment</option>
                    </select>
                </div>
                
                <div id="mobileBankingFields" class="mobile-banking-fields">
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="tel" class="form-control" name="mobile_number">
                    </div>
                </div>
                
                <div id="cardPaymentFields" class="card-payment-fields">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" class="form-control" name="card_number">
                    </div>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" class="form-control" name="expiry_date" placeholder="MM/YY">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" class="form-control" name="cvv">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="confirmPayment()">Confirm Payment</button>
                    <button type="button" class="btn btn-secondary" onclick="hidePayment()">Back</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function showPayment() {
            if (!document.getElementById('registrationForm').checkValidity()) {
                alert('Please fill all required fields');
                return;
            }
            document.getElementById('registrationForm').style.display = 'none';
            document.getElementById('paymentSection').style.display = 'block';
        }
        
        function hidePayment() {
            document.getElementById('registrationForm').style.display = 'block';
            document.getElementById('paymentSection').style.display = 'none';
        }
        
        function togglePaymentFields() {
            const method = document.getElementById('paymentMethod').value;
            document.getElementById('mobileBankingFields').style.display = 'none';
            document.getElementById('cardPaymentFields').style.display = 'none';
            
            if (method === 'mobile') {
                document.getElementById('mobileBankingFields').style.display = 'block';
            } else if (method === 'card') {
                document.getElementById('cardPaymentFields').style.display = 'block';
            }
        }
        
        function confirmPayment() {
            const method = document.getElementById('paymentMethod').value;
            let valid = true;
            
            if (method === 'mobile') {
                const mobileNumber = document.querySelector('input[name="mobile_number"]').value;
                if (!mobileNumber) {
                    alert('Please enter mobile number');
                    valid = false;
                }
            } else if (method === 'card') {
                const cardNumber = document.querySelector('input[name="card_number"]').value;
                const expiryDate = document.querySelector('input[name="expiry_date"]').value;
                const cvv = document.querySelector('input[name="cvv"]').value;
                
                if (!cardNumber || !expiryDate || !cvv) {
                    alert('Please fill all card details');
                    valid = false;
                }
            }
            
            if (valid) {
                document.getElementById('registrationForm').submit();
            }
        }
    </script>
</body>
</html>
