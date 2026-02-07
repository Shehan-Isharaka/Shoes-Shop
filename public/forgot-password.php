<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// If using composer:
require_once __DIR__ . '/../mail_app/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

function sendOtpEmail($toEmail, $toName, $otp) {
    $mail = new PHPMailer(true);

    // ✅ SMTP Config (update to your email provider)
    // Example: Gmail SMTP (needs App Password)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'shehan.isharaka.ac@gmail.com';      // change
    $mail->Password   = 'bvuk uues zmtq emnp';         // change
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('shehan.isharaka.ac@gmail.com', 'Pino Shoe Company'); // change
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body    = "
        <div style='font-family:Arial,sans-serif;line-height:1.6'>
            <h2>Password Reset Request</h2>
            <p>Hello <b>".htmlspecialchars($toName)."</b>,</p>
            <p>Your OTP code is:</p>
            <h1 style='letter-spacing:4px;margin:10px 0;'>$otp</h1>
            <p>This OTP is valid for <b>10 minutes</b>.</p>
            <p>If you did not request this, please ignore this email.</p>
            <hr>
            <small>Pino Shoe Company</small>
        </div>
    ";

    $mail->send();
}

if (isset($_POST['send_otp'])) {

    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $error = "Please enter your email.";
    } else {

        // Check customer exists & active
        $stmt = $connection->prepare("
            SELECT id, full_name
            FROM customers
            WHERE email = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $error = "No active account found for this email.";
        } else {
            $user = $res->fetch_assoc();

            // Generate 6-digit OTP
            $otp = (string)random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Save OTP in DB
            $upd = $connection->prepare("
                UPDATE customers
                SET reset_otp = ?, otp_expires = ?
                WHERE id = ?
            ");
            $upd->bind_param("ssi", $otp, $expires, $user['id']);
            $upd->execute();

            try {
                sendOtpEmail($email, $user['full_name'], $otp);
                $_SESSION['reset_email'] = $email; // store for next step
                header("Location: reset-password.php");
                exit;
            } catch (Exception $e) {
                // If email fails, show error
                $error = "OTP email could not be sent. Please check email settings.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include 'layout/header.php'; ?>

<div class="flex-grow-1 container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="display-6 mb-2"><i class="bi bi-shield-lock"></i></div>
                        <h4 class="fw-bold mb-1">Forgot Password</h4>
                        <p class="text-muted mb-0">Enter your email to receive an OTP.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg"
                                   placeholder="example@gmail.com" required>
                        </div>

                        <button type="submit" name="send_otp"
                                class="btn btn-dark w-100 rounded-pill py-2">
                            <i class="bi bi-envelope"></i> Send OTP
                        </button>

                        <div class="text-center mt-3">
                            <a href="customer-auth.php" class="text-decoration-none small">
                                ← Back to Login
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>