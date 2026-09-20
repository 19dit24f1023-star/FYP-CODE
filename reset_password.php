<?php

session_start();
include 'db.php';

$message = '';
$message_type = 'error';

$token = $_GET['token'] ?? '';

if (empty($token)) {

    $message = "Invalid password reset link.";

} else {

    // Check token and expiry
    $sql = "SELECT id, fullname, email
            FROM customers
            WHERE reset_token = ?
            AND reset_token_expiry > NOW()
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $token
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $customer = mysqli_fetch_assoc($result);


    if (!$customer) {

        $message =
            "This password reset link is invalid or has expired.";

    } else {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirm_password)) {

                $message = "Please fill in both password fields.";

            } elseif (strlen($password) < 8) {

                $message =
                    "Password must be at least 8 characters.";

            } elseif ($password !== $confirm_password) {

                $message =
                    "Passwords do not match.";

            } else {

                // Hash new password
                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                // Update password and remove token
                $update_sql = "UPDATE customers
                               SET password = ?,
                                   reset_token = NULL,
                                   reset_token_expiry = NULL
                               WHERE id = ?";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "si",
                    $hashed_password,
                    $customer['id']
                );

                if (mysqli_stmt_execute($update_stmt)) {

                    $_SESSION['auth_notice'] =
                        "Your password has been reset successfully. "
                        . "You can now login with your new password.";

                    $_SESSION['auth_notice_type'] =
                        "success";

                    $_SESSION['auth_active_tab'] =
                        "login";

                    header("Location: login.php");
                    exit();

                } else {

                    $message =
                        "Something went wrong. Please try again.";
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reset Password | SA Design</title>

    <style>

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f4f6fb;
            font-family:Arial,sans-serif;
        }

        .container{
            width:min(430px,90%);
            background:white;
            padding:35px;
            border-radius:20px;
            box-shadow:0 15px 45px rgba(0,0,0,.12);
        }

        h2{
            margin:0 0 10px;
            text-align:center;
            color:#142a83;
        }

        .description{
            text-align:center;
            color:#64748b;
            font-size:13px;
            line-height:1.6;
            margin-bottom:25px;
        }

        label{
            display:block;
            font-size:11px;
            font-weight:bold;
            margin-bottom:7px;
            color:#334155;
        }

        .field{
            margin-bottom:16px;
        }

        input{
            width:100%;
            height:45px;
            padding:0 13px;
            border:1px solid #dce2ef;
            border-radius:10px;
            outline:none;
        }

        input:focus{
            border-color:#2854c5;
        }

        button{
            width:100%;
            height:45px;
            margin-top:5px;
            border:none;
            border-radius:10px;
            background:#2854c5;
            color:white;
            font-weight:bold;
            cursor:pointer;
        }

        button:hover{
            background:#ef3a9b;
        }

        .message{
            padding:12px;
            border-radius:10px;
            margin-bottom:18px;
            font-size:12px;
            line-height:1.5;
        }

        .error{
            background:#fef2f2;
            color:#b91c1c;
        }

        .back{
            display:block;
            margin-top:18px;
            text-align:center;
            color:#64748b;
            font-size:12px;
            text-decoration:none;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Reset Password</h2>

    <p class="description">
        Create a new password for your SA Design account.
    </p>


    <?php if (!empty($message)): ?>

        <div class="message error">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($customer): ?>

        <form method="POST">

            <div class="field">

                <label for="password">
                    NEW PASSWORD
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter new password"
                    minlength="8"
                    required
                >

            </div>


            <div class="field">

                <label for="confirm_password">
                    CONFIRM PASSWORD
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Confirm new password"
                    minlength="8"
                    required
                >

            </div>


            <button type="submit">
                Reset Password
            </button>

        </form>

    <?php endif; ?>


    <a
        href="login.php"
        class="back"
    >
        ← Back to Login
    </a>

</div>

</body>

</html>