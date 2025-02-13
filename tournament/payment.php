<?php
session_start();

$servername = "localhost"; 
$username = "root";
$password = "";     
$dbname = "cse370-project";    


$conn = mysqli_connect($servername, $username, $password, $dbname);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$registration_info = $_SESSION['registration_info'] ?? null;
if (!$registration_info) {
    header('Location: index.php'); 
    exit();
}


$payment_method = '';
$mobile_number = '';
$card_number = '';
$expiry_date = '';
$cvv = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = $_POST['payment_method'];

    if ($payment_method === 'cash') {
        $_SESSION['payment_status'] = "Cash Payment Selected";
    } elseif ($payment_method === 'mobile') {
        $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
        $_SESSION['payment_status'] = "Mobile Payment Selected, Mobile Number: $mobile_number";
    } elseif ($payment_method === 'card') {
        $card_number = mysqli_real_escape_string($conn, $_POST['card_number']);
        $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
        $cvv = mysqli_real_escape_string($conn, $_POST['cvv']);
        $_SESSION['payment_status'] = "Card Payment Selected, Card Number: $card_number";
    }

    $sql = "INSERT INTO payments (payment_method) VALUES (?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $payment_method);

    if ($stmt->execute()) {
        echo "Payment information has been successfully stored!";
    } else {
        echo "Error: " . $stmt->error;
    }
    header('Location: ../tournament/confirmation.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        select, input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        button {
            background-color: #53A9A8;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #12525c;
        }
        .hidden {
            display: none;
        }
    </style>
    <script>
        function togglePaymentFields() {
            const paymentMethod = document.querySelector('select[name="payment_method"]').value;
            const cashFields = document.querySelector('.cash-fields');
            const mobileFields = document.querySelector('.mobile-fields');
            const cardFields = document.querySelector('.card-fields');

            cashFields.classList.toggle('hidden', paymentMethod !== 'cash');
            mobileFields.classList.toggle('hidden', paymentMethod !== 'mobile');
            cardFields.classList.toggle('hidden', paymentMethod !== 'card');
        }
    </script>
</head>
<body>

<div class="container">
    <h3>Payment Information</h3>
    <form method="POST" action="payment.php">
        <label for="payment_method">Payment Method</label>
        <select name="payment_method" onchange="togglePaymentFields()">
            <option value="cash" selected>Cash</option>
            <option value="mobile">Mobile Banking</option>
            <option value="card">Card</option>
        </select>

        <div class="cash-fields">
            <p>You have selected Cash payment. Please prepare the amount for payment.</p>
        </div>

        <div class="mobile-fields hidden">
            <label for="mobile_number">Mobile Number</label>
            <input type="text" name="mobile_number" placeholder="Enter your mobile number">
        </div>

        <div class="card-fields hidden">
            <label for="card_number">Card Number</label>
            <input type="text" name="card_number" placeholder="Enter card number">

            <label for="expiry_date">Expiry Date (MM/YY)</label>
            <input type="text" name="expiry_date" placeholder="MM/YY">

            <label for="cvv">CVV</label>
            <input type="text" name="cvv" placeholder="Enter CVV">
        </div>

        <button type="submit">Confirm Payment</button>
        
    </form>
</div>

</body>
</html>
