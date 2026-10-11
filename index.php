<?php
session_start();
require_once 'config.php';

$error = '';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: everyone.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($phone) && !empty($password)) {
        $stmt = $conn->prepare("SELECT name, password_text FROM users WHERE phone_number = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if ($password === $row['password_text']) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_name'] = $row['name'];
                $stmt->close();
                header('Location: everyone.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill out all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Expenditure Gateway</title>
    <style>
        :root {
            --bg-1: #130d0a;
            --bg-2: #1f1712;
            --bg-3: #2a1f1b;
            --glass: rgba(36, 24, 18, 0.55);
            --glass-strong: rgba(24, 16, 12, 0.78);
            --line: rgba(212, 166, 121, 0.28);
            --text: #f8ead8;
            --muted: #e9d3ae;
            --primary: #d7aa76;
            --primary-strong: #b87b42;
            --violet: #c8965d;
            --success: #d4b07b;
            --danger: #f5b7a2;
            --shadow: rgba(20, 12, 7, 0.7);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            color: var(--text);
            background-image:
                linear-gradient(rgba(20, 12, 7, 0.66), rgba(20, 12, 7, 0.78)),
                url('bg_img.jpeg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow-x: hidden;
        }
        body::before {
            content: "";
            position: fixed;
            inset: -20% -10% auto auto;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(215, 170, 118, 0.18), transparent 70%);
            filter: blur(30px);
            pointer-events: none;
        }
        body::after {
            content: "RARE CAFE";
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: clamp(3rem, 6vw, 7rem);
            letter-spacing: 0.1em;
            font-weight: 800;
            color: rgba(217, 180, 128, 0.12);
            text-shadow: 0 0 28px rgba(215, 170, 118, 0.14);
            pointer-events: none;
            z-index: 0;
        }
        .login-box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 2.6rem 2.2rem;
            border-radius: 28px;
            border: 1px solid rgba(255,255,255,0.1);
            background: linear-gradient(180deg, rgba(44, 28, 20, 0.75), rgba(26, 18, 13, 0.55));
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 32px 80px rgba(20, 12, 7, 0.7), inset 0 1px 0 rgba(255,255,255,0.08), 0 0 40px rgba(215, 170, 118, 0.12);
        }
        .login-box::before {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: 27px;
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.1), rgba(183, 123, 66, 0.08), transparent 70%);
            pointer-events: none;
        }
        h2 {
            position: relative;
            text-align: center;
            color: #f5e7d0;
            margin-bottom: 1.5rem;
            letter-spacing: 0.09em;
            font-size: clamp(1.7rem, 3vw, 2.3rem);
            text-transform: uppercase;
            text-shadow: 0 0 20px rgba(215, 170, 118, 0.45);
        }
        .group { margin-bottom: 1.25rem; position: relative; z-index: 1; }
        label { display: block; margin-bottom: 0.5rem; color: var(--muted); font-size: 0.9rem; }
        input {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(212, 166, 121, 0.25);
            background: rgba(32, 23, 18, 0.5);
            color: var(--text);
            font-size: 1rem;
            transition: 0.2s ease;
            backdrop-filter: blur(8px);
        }
        input::placeholder { color: rgba(233, 211, 174, 0.64); }
        input:focus {
            outline: none;
            border-color: rgba(215, 170, 118, 0.9);
            box-shadow: 0 0 0 4px rgba(215, 170, 118, 0.15), 0 12px 28px rgba(184, 123, 66, 0.18);
        }
        button {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 0.95rem;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #bf7b46 40%, var(--violet));
            color: #1a110d;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            margin-top: 0.5rem;
            box-shadow: 0 18px 30px rgba(184, 123, 66, 0.28);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 35px rgba(184, 123, 66, 0.34);
            filter: brightness(1.04);
        }
        .error {
            position: relative;
            z-index: 1;
            background: rgba(127, 29, 29, 0.52);
            color: var(--danger);
            padding: 0.8rem 0.9rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            border: 1px solid rgba(245, 183, 162, 0.28);
        }

        @media (max-width: 480px) {
            body {
                padding: 1.2rem 0.8rem;
                background-position: center center;
            }
            body::after {
                font-size: 2.4rem;
                letter-spacing: 0.08em;
                opacity: 0.8;
            }
            .login-box {
                padding: 1.8rem 1rem;
                border-radius: 22px;
            }
            h2 {
                font-size: 1.5rem;
                letter-spacing: 0.06em;
            }
            input,
            button {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Expenditure Login</h2>
    <?php if(!empty($error)): ?> <div class="error"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?>
    <form action="index.php" method="POST">
        <div class="group">
            <label>Phone Number</label>
            <input type="text" name="phone" required autocomplete="off">
        </div>
        <div class="group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Sign In</button>
    </form>
</div>
</body>
</html>