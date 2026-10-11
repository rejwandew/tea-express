<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Expenditure Dashboard - Modify</title>
    <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
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
            color: var(--text);
            -webkit-tap-highlight-color: transparent;
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
            inset: -18% auto auto -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(215, 170, 118, 0.12), transparent 70%);
            filter: blur(40px);
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

        nav {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            background: rgba(42, 28, 20, 0.62);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 18px 32px rgba(20, 12, 7, 0.18), 0 0 28px rgba(215, 170, 118, 0.08);
        }
        nav button {
            padding: 1rem 0.3rem;
            background: transparent;
            border: none;
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.02em;
            transition: all 0.2s ease;
            text-align: center;
        }
        nav button:hover { color: var(--text); background: rgba(193, 146, 98, 0.08); }
        nav button.active {
            color: #f9ecdc;
            border-bottom: 3px solid var(--primary);
            background: linear-gradient(180deg, rgba(195, 138, 89, 0.14), rgba(36, 25, 20, 0.44));
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 0 20px rgba(215, 170, 118, 0.08);
        }

        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; position: relative; z-index: 1; }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, rgba(42, 28, 20, 0.72), rgba(27, 19, 14, 0.46));
            padding: 1.5rem;
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 1.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 60px rgba(20, 12, 7, 0.28), inset 0 1px 0 rgba(255,255,255,0.08), 0 0 30px rgba(215, 170, 118, 0.08);
            gap: 1rem;
        }
        .app-header h2 { color: #f5e7d0; font-size: 1.3rem; word-break: break-word; text-shadow: 0 0 18px rgba(215, 170, 118, 0.35); }
        .app-header p { font-size: 0.85rem; }
        .logout-btn {
            color: #f7d8cb;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.95rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: rgba(153, 74, 48, 0.18);
            border: 1px solid rgba(245, 183, 162, 0.2);
            box-shadow: 0 10px 24px rgba(153, 74, 48, 0.12);
            white-space: nowrap;
        }

        .page { display: none; }
        .page.active { display: block; }

        .columns { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .col-card {
            position: relative;
            background: linear-gradient(180deg, rgba(42, 28, 20, 0.72), rgba(27, 19, 14, 0.54));
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.08);
            padding: 1.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 55px rgba(20, 12, 7, 0.26), inset 0 1px 0 rgba(255,255,255,0.06), 0 0 24px rgba(215, 170, 118, 0.04);
        }
        .col-card::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, rgba(215,170,118,0.8), transparent 60%);
            opacity: 0.7;
        }
        .col-card h3 { border-bottom: 1px solid rgba(212, 166, 121, 0.26); padding-bottom: 0.6rem; margin-bottom: 1rem; font-size: 1.1rem; }

        .radio-selector { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .radio-selector label {
            background: rgba(212, 166, 121, 0.08);
            padding: 0.8rem 1.3rem;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 800;
            width: 100%;
            text-align: center;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            word-break: break-word;
        }
        .radio-selector input { display: none; }
        .radio-selector input:checked + label {
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.28), rgba(184, 123, 66, 0.22), rgba(147, 96, 57, 0.18));
            color: #fff7ee;
            border-color: rgba(215, 170, 118, 0.5);
            box-shadow: 0 16px 30px rgba(184, 123, 66, 0.22);
        }

        .tea-counter-card {
            background: rgba(35, 24, 18, 0.5);
            border: 1px solid rgba(212, 166, 121, 0.2);
            border-radius: 18px;
            padding: 1rem 1.2rem;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(12px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 0 24px rgba(215,170,118,0.03);
            transition: transform 0.2s ease;
            gap: 1rem;
        }
        .tea-counter-card:hover { transform: translateY(-1px); }
        .tea-title { font-size: 1.05rem; font-weight: 600; color: var(--text); word-break: break-word; }
        .counter-controls { display: flex; align-items: center; gap: 1.2rem; }

        .counter-btn {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 50%;
            border: none;
            font-size: 1.5rem;
            font-weight: 800;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), #9a5f38);
            color: #1a110d;
            display: flex;
            justify-content: center;
            align-items: center;
            user-select: none;
            box-shadow: 0 10px 24px rgba(184, 123, 66, 0.24);
        }
        .counter-btn:active { opacity: 0.88; }
        .big-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            min-width: 52px;
            text-align: center;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.35rem; color: var(--muted); font-size: 0.9rem; }
        .form-group input {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border-radius: 12px;
            border: 1px solid rgba(212, 166, 121, 0.22);
            background: rgba(25, 18, 13, 0.5);
            color: var(--text);
            font-size: 1rem;
            backdrop-filter: blur(8px);
        }
        .submit-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #d7aa76, #b87b42);
            border: none;
            border-radius: 12px;
            color: #1a110d;
            font-weight: 800;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 14px 26px rgba(184, 123, 66, 0.28);
        }
        .submit-btn:hover { filter: brightness(1.04); }

        .outgoing-list-wrapper {
            margin-top: 1.5rem;
            border-top: 1px dashed rgba(212, 166, 121, 0.28);
            padding-top: 1.5rem;
        }
        .outgoing-item {
            background: rgba(30, 21, 16, 0.5);
            border: 1px solid rgba(212, 166, 121, 0.2);
            padding: 0.8rem 1rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.6rem;
            gap: 0.75rem;
            word-break: break-word;
        }
        .outgoing-item-details { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; flex: 1; }
        .outgoing-item-amt { font-size: 1.1rem; font-weight: 700; color: #f7d6b2; white-space: nowrap; }
        .outgoing-total-card {
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.22), rgba(184, 123, 66, 0.18));
            border: 1px solid rgba(212, 166, 121, 0.35);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-weight: 800;
            font-size: 1.1rem;
            word-break: break-word;
        }

        @media (max-width: 768px) {
            nav button { font-size: 0.75rem; padding: 0.8rem 0.15rem; }
            .container { margin: 1rem auto; }
            .app-header {
                flex-wrap: wrap;
                flex-direction: row;
                align-items: center;
                padding: 1rem;
                gap: 0.75rem;
            }
            .app-header > div {
                flex: 1 1 auto;
                min-width: 0;
            }
            .app-header a {
                margin-left: auto;
            }
            .radio-selector { flex-direction: column; gap: 0.6rem; }
            body::after {
                font-size: clamp(2rem, 9vw, 4rem);
                letter-spacing: 0.08em;
                opacity: 0.7;
            }
        }

        @media (max-width: 480px) {
            nav {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
            nav button {
                font-size: 0.68rem;
                padding: 0.7rem 0.1rem;
            }
            .container {
                padding: 0 0.75rem;
            }
            .app-header {
                flex-direction: column;
                align-items: stretch;
            }
            .app-header > div {
                width: 100%;
            }
            .app-header h2 {
                font-size: 1.15rem;
                line-height: 1.3;
                word-break: break-word;
            }
            .app-header,
            .col-card {
                padding: 1rem;
            }
            .tea-counter-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.7rem;
            }
            .counter-controls {
                width: 100%;
                justify-content: space-between;
            }
            .big-number {
                min-width: 42px;
                font-size: 1.45rem;
            }
            .submit-btn,
            .logout-btn {
                width: 100%;
            }
            .outgoing-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.4rem;
            }
            .outgoing-item-amt {
                font-size: 1rem;
            }
        }

        /* ── AI Voice Input Button ───────────────────────────────────────────── */
        .voice-ai-btn {
            width: 100%;
            padding: 0.9rem 1rem;
            margin-bottom: 1.4rem;
            border-radius: 16px;
            border: 1px solid rgba(215, 170, 118, 0.38);
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.14), rgba(184, 123, 66, 0.1));
            color: #f7d6b2;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(184, 123, 66, 0.1);
            backdrop-filter: blur(8px);
            letter-spacing: 0.02em;
        }
        .voice-ai-btn:hover {
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.24), rgba(184, 123, 66, 0.18));
            box-shadow: 0 14px 32px rgba(184, 123, 66, 0.18);
            transform: translateY(-1px);
        }
        .voice-ai-btn.recording {
            background: linear-gradient(135deg, rgba(210, 65, 50, 0.32), rgba(170, 40, 30, 0.26));
            border-color: rgba(245, 140, 120, 0.55);
            color: #f9c4b8;
            animation: voice-pulse 1.5s ease-in-out infinite;
        }
        .voice-ai-btn.processing {
            background: linear-gradient(135deg, rgba(90, 130, 200, 0.2), rgba(60, 100, 170, 0.14));
            border-color: rgba(130, 170, 235, 0.4);
            color: #c0d0f0;
            cursor: not-allowed;
            pointer-events: none;
        }
        @keyframes voice-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(210, 65, 50, 0.4); }
            50%       { box-shadow: 0 0 0 12px rgba(210, 65, 50, 0); }
        }
        .voice-status-bar {
            display: none;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.82rem;
            color: var(--muted);
            margin-bottom: 1.2rem;
            padding: 0.55rem 0.85rem;
            background: rgba(20, 14, 10, 0.45);
            border-radius: 10px;
            border: 1px solid rgba(212, 166, 121, 0.15);
        }
        .voice-status-bar.visible { display: flex; }
        .voice-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--primary);
            flex-shrink: 0;
            animation: dot-blink 1s ease-in-out infinite;
        }
        @keyframes dot-blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }

        /* ── AI Confirmation Modal ───────────────────────────────────────────── */
        .ai-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(8, 5, 3, 0.75);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }
        .ai-overlay.open { display: flex; }
        .ai-modal {
            background: linear-gradient(180deg, rgba(46, 31, 23, 0.97), rgba(27, 18, 13, 0.99));
            border: 1px solid rgba(215, 170, 118, 0.32);
            border-radius: 28px;
            padding: 1.8rem;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 50px 100px rgba(8, 5, 3, 0.75),
                        inset 0 1px 0 rgba(255,255,255,0.07),
                        0 0 60px rgba(215, 170, 118, 0.1);
        }
        .ai-modal::-webkit-scrollbar { width: 4px; }
        .ai-modal::-webkit-scrollbar-track { background: transparent; }
        .ai-modal::-webkit-scrollbar-thumb { background: rgba(215,170,118,0.3); border-radius: 4px; }
        .ai-modal-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #f5e7d0;
            margin-bottom: 0.3rem;
            text-shadow: 0 0 18px rgba(215,170,118,0.3);
        }
        .ai-modal-subtitle {
            font-size: 0.82rem;
            color: rgba(233,211,174,0.55);
            margin-bottom: 1.3rem;
        }
        .ai-transcript-box {
            background: rgba(18, 12, 8, 0.6);
            border: 1px solid rgba(212, 166, 121, 0.18);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 0.88rem;
            color: var(--muted);
            margin-bottom: 1.5rem;
            line-height: 1.55;
            font-style: italic;
            word-break: break-word;
        }
        .ai-section-title {
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 0.55rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid rgba(212, 166, 121, 0.18);
        }
        .ai-col-labels {
            display: flex;
            gap: 0.5rem;
            font-size: 0.7rem;
            color: rgba(233,211,174,0.45);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
            padding: 0 2px;
        }
        .ai-row {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        .ai-row.sales-row    { grid-template-columns: 1fr 72px 32px; }
        .ai-row.expense-row  { grid-template-columns: 1fr 80px 80px 32px; }
        .ai-input {
            padding: 0.55rem 0.7rem;
            border-radius: 9px;
            border: 1px solid rgba(212, 166, 121, 0.2);
            background: rgba(20, 13, 9, 0.6);
            color: var(--text);
            font-size: 0.88rem;
            width: 100%;
            transition: border-color 0.2s ease;
        }
        .ai-input:focus {
            outline: none;
            border-color: rgba(215, 170, 118, 0.65);
            box-shadow: 0 0 0 3px rgba(215, 170, 118, 0.1);
        }
        .ai-remove-btn {
            background: rgba(190, 55, 45, 0.2);
            border: 1px solid rgba(245, 140, 120, 0.28);
            color: #f5b7a2;
            width: 32px; height: 32px; min-width: 32px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s ease;
        }
        .ai-remove-btn:hover { background: rgba(190, 55, 45, 0.38); }
        .ai-empty-msg {
            color: rgba(233,211,174,0.38);
            font-size: 0.84rem;
            padding: 0.5rem 0.2rem;
            font-style: italic;
        }
        .ai-divider { height: 1px; background: rgba(212,166,121,0.14); margin: 1.2rem 0; }
        .ai-modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .ai-cancel-btn {
            flex: 1;
            padding: 0.85rem;
            border: 1px solid rgba(212, 166, 121, 0.25);
            border-radius: 13px;
            background: rgba(22, 15, 11, 0.5);
            color: var(--muted);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .ai-cancel-btn:hover { background: rgba(22, 15, 11, 0.8); color: var(--text); }
        .ai-confirm-btn {
            flex: 2;
            padding: 0.85rem;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, #d7aa76, #b87b42);
            color: #1a110d;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 12px 28px rgba(184, 123, 66, 0.28);
            transition: filter 0.2s ease, transform 0.2s ease;
        }
        .ai-confirm-btn:hover:not(:disabled) { filter: brightness(1.06); transform: translateY(-1px); }
        .ai-confirm-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        @media (max-width: 480px) {
            .ai-modal { padding: 1.2rem; border-radius: 20px; }
            .ai-row.expense-row { grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; }
            .ai-row.expense-row .ai-remove-btn { grid-column: 2; grid-row: 2; justify-self: end; }
        }
    </style>
</head>
<body>

<nav>
    <button id="btn-page1" onclick="window.location.href='everyone.php'">Everyone</button>
    <button id="btn-page2" onclick="window.location.href='individual.php'">Individual</button>
    <button id="btn-page3" class="active" onclick="window.location.href='modify.php'">Modify</button>
    <button id="btn-page4" onclick="window.location.href='due.php'">Due</button>
    <button id="btn-page5" onclick="window.location.href='invest.php'">Invest</button>
</nav>

<div class="container">
    <div class="app-header">
        <div>
            <h2>User: <span id="header-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Member'); ?></span></h2>
            <p style="color: #e9d3ae;">Date: <span id="header-date"></span></p>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div id="page3" class="page active">
        <div class="columns">
            <div class="col-card">
                <h3>Update Daily Ledger</h3>

                <!-- ── AI Voice Input Button ────────────────────────────── -->
                <button id="voice-ai-btn" class="voice-ai-btn" onclick="toggleVoiceRecording()">
                    🎤 Tap to Record &nbsp;<span style="font-weight:400;font-size:0.82rem;opacity:0.75">(AI Voice Input)</span>
                </button>
                <div id="voice-status-bar" class="voice-status-bar">
                    <span class="voice-dot"></span>
                    <span id="voice-status-text">Listening…</span>
                </div>
                <!-- ───────────────────────────────────────────────────────── -->

                <div class="radio-selector">
                    <input type="radio" id="rad-in" name="ledger_type" value="incoming" checked onclick="toggleEditSection()">
                    <label for="rad-in">Incoming</label>
                    
                    <input type="radio" id="rad-out" name="ledger_type" value="outgoing" onclick="toggleEditSection()">
                    <label for="rad-out">Outgoing</label>
                </div>

                <div id="edit-incoming-zone">
                    <p style="color:#e9d3ae; margin-bottom:1rem;">Adjust your live tracking records below:</p>
                    <div id="tea-container">
                        
                        <div class="tea-counter-card">
                            <span class="tea-title">1. Dudh Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Dudh Cha', -1)">-</button>
                                <span class="big-number" id="num-Dudh-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Dudh Cha', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">2. Lal Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Lal Cha', -1)">-</button>
                                <span class="big-number" id="num-Lal-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Lal Cha', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">3. Lebu Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Lebu Cha', -1)">-</button>
                                <span class="big-number" id="num-Lebu-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Lebu Cha', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">4. Tetul Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Tetul Cha', -1)">-</button>
                                <span class="big-number" id="num-Tetul-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Tetul Cha', 1)">+</button>
                            </div>
                        </div>
                        
                        <div class="tea-counter-card">
                            <span class="tea-title">5. Pora Ruti [Small]</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Pora Ruti [Small]', -1)">-</button>
                                <span class="big-number" id="num-Pora-Ruti-[Small]">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Pora Ruti [Small]', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">6. Pora Ruti [Large]</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Pora Ruti [Large]', -1)">-</button>
                                <span class="big-number" id="num-Pora-Ruti-[Large]">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Pora Ruti [Large]', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">7. Horlicks Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Horlicks Cha', -1)">-</button>
                                <span class="big-number" id="num-Horlicks-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Horlicks Cha', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">8. Malai Cha</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Malai Cha', -1)">-</button>
                                <span class="big-number" id="num-Malai-Cha">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Malai Cha', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">9. Chicken Singara</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Chicken Singara', -1)">-</button>
                                <span class="big-number" id="num-Chicken-Singara">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Chicken Singara', 1)">+</button>
                            </div>
                        </div>

                        <div class="tea-counter-card">
                            <span class="tea-title">10. Dudh Danish</span>
                            <div class="counter-controls">
                                <button class="counter-btn" onclick="updateTeaCount('Dudh Danish', -1)">-</button>
                                <span class="big-number" id="num-Dudh-Danish">0</span>
                                <button class="counter-btn" onclick="updateTeaCount('Dudh Danish', 1)">+</button>
                            </div>
                        </div>

                    </div>
                </div>

                <div id="edit-outgoing-zone" style="display: none;">
                    <div class="form-group">
                        <label>Ingredient Name</label>
                        <input type="text" id="out-ingredient" placeholder="e.g., Rice, Sugar">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="text" id="out-quantity" placeholder="e.g., 5 kg, 2 packets">
                    </div>
                    <div class="form-group">
                        <label>Amount (৳)</label>
                        <input type="number" id="out-amount" placeholder="e.g., 500">
                    </div>
                    <button class="submit-btn" onclick="submitOutgoing()">Submit Entry</button>

                    <div class="outgoing-list-wrapper">
                        <h4 style="color: #d7aa76; margin-bottom: 0.75rem;">Current Outgoing Expenses</h4>
                        <div id="outgoing-items-container"></div>
                        <div class="outgoing-total-card">
                            <span>Total Outgoing Sum:</span>
                            <span id="outgoing-grand-total">৳0</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    try {
        document.getElementById('header-date').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch(e) {
        console.error("Date assignment failed:", e);
    }

    const SUPABASE_URL = "<?php echo defined('SUPABASE_URL') ? SUPABASE_URL : ''; ?>";
    const SUPABASE_KEY = "<?php echo defined('SUPABASE_KEY') ? SUPABASE_KEY : ''; ?>";
    const USER_PHONE = "<?php echo $_SESSION['user_phone'] ?? ''; ?>";
    const USER_NAME = "<?php echo $_SESSION['user_name'] ?? ''; ?>";

    function getLocalDateString() {
        const d = new Date();
        const offset = d.getTimezoneOffset();
        const localizedDate = new Date(d.getTime() - (offset * 60 * 1000));
        return localizedDate.toISOString().split('T')[0];
    }

    let sbClient = null;
    let globalTeaRecords = [
        { tea_type: "Dudh Cha", quantity: 0 },
        { tea_type: "Lal Cha", quantity: 0 },
        { tea_type: "Lebu Cha", quantity: 0 },
        { tea_type: "Tetul Cha", quantity: 0 },
        { tea_type: "Pora Ruti [Small]", quantity: 0 },
        { tea_type: "Pora Ruti [Large]", quantity: 0 },
        { tea_type: "Horlicks Cha", quantity: 0 },
        { tea_type: "Malai Cha", quantity: 0 },
        { tea_type: "Chicken Singara", quantity: 0 },
        { tea_type: "Dudh Danish", quantity: 0 }
    ];

    if (typeof supabase !== 'undefined' || typeof Supabase !== 'undefined') {
        const clientInitializer = typeof Supabase !== 'undefined' ? Supabase : supabase;
        if (SUPABASE_URL && SUPABASE_KEY) {
            try {
                sbClient = clientInitializer.createClient(SUPABASE_URL, SUPABASE_KEY);
            } catch (err) {
                console.error("Supabase initialization failed:", err);
            }
        }
    } else {
        console.error("Critical Mirror Block: The hosting provider is blocking external JS files.");
    }

    function toggleEditSection() {
        const isIn = document.getElementById('rad-in').checked;
        document.getElementById('edit-incoming-zone').style.display = isIn ? 'block' : 'none';
        document.getElementById('edit-outgoing-zone').style.display = isIn ? 'none' : 'block';
    }

    async function fetchInitialData() {
        if (!sbClient) return;
        try {
            const todayStr = getLocalDateString();
            
            // Reads strictly from daily isolated live view tracking table
            let { data: tea, error } = await sbClient
                .from('daily_live_tea')
                .select('*')
                .eq('phone_number', USER_PHONE)
                .eq('created_at', todayStr);
                
            if (error) throw error;
            
            globalTeaRecords.forEach(t => {
                const cleanType = t.tea_type.trim();
                const safeId = cleanType.replace(/\s+/g, '-');
                const element = document.getElementById(`num-${safeId}`);
                if(element) element.textContent = "0";
                t.quantity = 0;
            });

            if (tea && tea.length > 0) {
                tea.forEach(dbItem => {
                    const match = globalTeaRecords.find(g => g.tea_type.trim() === dbItem.tea_type.trim());
                    if (match) {
                        match.quantity = dbItem.quantity;
                    }

                    const cleanType = dbItem.tea_type.trim();
                    const safeId = cleanType.replace(/\s+/g, '-');
                    const element = document.getElementById(`num-${safeId}`);
                    if(element) {
                        element.textContent = dbItem.quantity;
                    }
                });
            }
        } catch(err) {
            console.error("Error reading live database metrics:", err.message);
        }
        await fetchOutgoingData();
    }

    async function fetchOutgoingData() {
        if (!sbClient) return;
        try {
            let { data: outgoing, error } = await sbClient.from('outgoing_expenses').select('*');
            if (error) return;

            const container = document.getElementById('outgoing-items-container');
            container.innerHTML = '';
            let totalSum = 0;

            (outgoing || []).forEach(item => {
                totalSum += parseFloat(item.amount || 0);
                const div = document.createElement('div');
                div.className = 'outgoing-item';
                div.innerHTML = `
                    <div class="outgoing-item-details">
                        <strong>${item.ingredient_name}</strong>
                        <span style="color: #e9d3ae; font-size: 0.85rem;">Qty: ${item.quantity} (By ${item.user_name})</span>
                    </div>
                    <div class="outgoing-item-amt">৳${item.amount}</div>
                `;
                container.appendChild(div);
            });

            document.getElementById('outgoing-grand-total').textContent = '৳' + totalSum;
        } catch(e) {
            console.error(e);
        }
    }

    async function updateTeaCount(teaType, step) {
        const match = globalTeaRecords.find(t => t.tea_type.trim() === teaType.trim());
        
        let currentQty = match ? parseInt(match.quantity) : 0;
        let finalQty = currentQty + step;
        if (finalQty < 0) finalQty = 0;

        const safeId = teaType.trim().replace(/\s+/g, '-');
        const numElem = document.getElementById(`num-${safeId}`);
        if(numElem) {
            numElem.textContent = finalQty;
        }

        if(match) {
            match.quantity = finalQty;
        }

        if (sbClient) {
            const todayStr = getLocalDateString();
            const payload = { 
                tea_type: teaType, 
                quantity: finalQty, 
                phone_number: USER_PHONE, 
                user_name: USER_NAME,
                created_at: todayStr
            };
            
            // 1. Write status to today's transient active ledger using upsert with constraint safety configurations
            let { error: liveError } = await sbClient
                .from('daily_live_tea')
                .upsert(payload, { onConflict: 'phone_number,tea_type,created_at' });

            if (liveError) {
                console.error("Live view tracking write failed, trying structural fallback query update:", liveError.message);
                await sbClient.from('daily_live_tea')
                    .update({ quantity: finalQty, user_name: USER_NAME })
                    .eq('phone_number', USER_PHONE)
                    .eq('tea_type', teaType)
                    .eq('created_at', todayStr);
            }

            // 2. Mirror records down to long-term database history file
            let { error: histError } = await sbClient
                .from('incoming_tea')
                .upsert(payload, { onConflict: 'phone_number,tea_type,created_at' });

            if (histError) {
                console.error("History file log sync write failed, trying structural fallback query update:", histError.message);
                await sbClient.from('incoming_tea')
                    .update({ quantity: finalQty, user_name: USER_NAME })
                    .eq('phone_number', USER_PHONE)
                    .eq('tea_type', teaType)
                    .eq('created_at', todayStr);
            }
        }
    }

    async function submitOutgoing() {
        const nameInput = document.getElementById('out-ingredient');
        const qtyInput = document.getElementById('out-quantity');
        const amtInput = document.getElementById('out-amount');

        if(!nameInput.value || !qtyInput.value || !amtInput.value) {
            alert('Please complete all form options.');
            return;
        }

        if (sbClient) {
            const { error } = await sbClient.from('outgoing_expenses').insert([
                {
                    phone_number: USER_PHONE,
                    user_name: USER_NAME,
                    ingredient_name: nameInput.value,
                    quantity: qtyInput.value,
                    amount: parseFloat(amtInput.value)
                }
            ]);
            if (error) console.error(error.message);
        }

        nameInput.value = '';
        qtyInput.value = '';
        amtInput.value = '';
        
        await fetchOutgoingData();
    }

    if (sbClient) {
        sbClient
            .channel('db-expenditures')
            .on('postgres_changes', { event: '*', schema: 'public', table: 'daily_live_tea' }, () => { fetchInitialData(); })
            .on('postgres_changes', { event: '*', schema: 'public', table: 'outgoing_expenses' }, () => { fetchOutgoingData(); })
            .subscribe();

        fetchInitialData();
    }

</script>

<!-- ═══════════════════════════════════════════════════════════════════════════
     AI VOICE CONFIRMATION MODAL
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="ai-overlay" class="ai-overlay" onclick="handleOverlayClick(event)">
    <div class="ai-modal">
        <div class="ai-modal-title">🤖 AI Voice — Review & Confirm</div>
        <div class="ai-modal-subtitle">Check the detected items, edit if needed, then save.</div>

        <!-- Transcript -->
        <div class="ai-transcript-box" id="ai-transcript">"..."</div>

        <!-- Sales Section -->
        <div class="ai-section-title">🍵 Sales to Add</div>
        <div class="ai-col-labels" id="ai-sales-labels" style="padding-right:40px">
            <span style="flex:1">Item</span><span style="width:72px">Qty</span>
        </div>
        <div id="ai-sales-rows"></div>

        <div class="ai-divider"></div>

        <!-- Expenses Section -->
        <div class="ai-section-title">💸 Expenses to Add</div>
        <div class="ai-col-labels" id="ai-expense-labels" style="padding-right:40px">
            <span style="flex:1">Ingredient</span>
            <span style="width:80px">Quantity</span>
            <span style="width:80px">Amount ৳</span>
        </div>
        <div id="ai-expense-rows"></div>

        <div class="ai-modal-actions">
            <button class="ai-cancel-btn" onclick="closeAiModal()">Cancel</button>
            <button class="ai-confirm-btn" id="ai-confirm-btn" onclick="confirmAndSave()">✓ Confirm &amp; Save</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     AI VOICE INPUT — JAVASCRIPT
     ═══════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    // ── State ─────────────────────────────────────────────────────────────────
    let mediaRecorder  = null;
    let audioChunks    = [];
    let isRecording    = false;
    let mediaStream    = null;
    let aiData         = { transcript: '', sales: [], expenses: [] };

    const MENU_ITEMS = [
        'Dudh Cha', 'Lal Cha', 'Lebu Cha', 'Tetul Cha',
        'Horlicks Cha', 'Malai Cha',
        'Pora Ruti [Small]', 'Pora Ruti [Large]',
        'Chicken Singara', 'Dudh Danish'
    ];

    // ── Recording toggle ──────────────────────────────────────────────────────
    window.toggleVoiceRecording = async function () {
        if (isRecording) { stopRecording(); } else { await startRecording(); }
    };

    async function startRecording() {
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        } catch (err) {
            alert('Microphone access denied.\nPlease allow microphone permission in your browser settings and try again.');
            return;
        }

        audioChunks = [];
        const mimeType = getSupportedMimeType();
        try {
            mediaRecorder = new MediaRecorder(mediaStream, mimeType ? { mimeType } : {});
        } catch (e) {
            mediaRecorder = new MediaRecorder(mediaStream);
        }

        mediaRecorder.addEventListener('dataavailable', e => {
            if (e.data && e.data.size > 0) audioChunks.push(e.data);
        });

        mediaRecorder.addEventListener('stop', async () => {
            const finalMime = mediaRecorder.mimeType || mimeType || 'audio/webm';
            const blob = new Blob(audioChunks, { type: finalMime });
            await sendToGemini(blob, finalMime.split(';')[0]);
        });

        mediaRecorder.start();
        isRecording = true;
        setButtonState('recording');
    }

    function stopRecording() {
        isRecording = false;
        if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
        if (mediaStream) { mediaStream.getTracks().forEach(t => t.stop()); mediaStream = null; }
        setButtonState('processing');
    }

    function getSupportedMimeType() {
        const candidates = [
            'audio/webm;codecs=opus', 'audio/webm',
            'audio/ogg;codecs=opus',  'audio/mp4'
        ];
        return candidates.find(t => MediaRecorder.isTypeSupported(t)) || '';
    }

    function setButtonState(state) {
        const btn    = document.getElementById('voice-ai-btn');
        const bar    = document.getElementById('voice-status-bar');
        const txt    = document.getElementById('voice-status-text');
        btn.className = 'voice-ai-btn ' + state;

        if (state === 'recording') {
            btn.innerHTML  = '⏹️ Stop &amp; Send';
            btn.disabled   = false;
            bar.classList.add('visible');
            txt.textContent = 'Listening… tap again to stop recording.';
        } else if (state === 'processing') {
            btn.innerHTML  = '⏳ Processing with AI…';
            btn.disabled   = true;
            txt.textContent = 'Sending audio to Gemini AI…';
        } else {
            btn.innerHTML  = '🎤 Tap to Record &nbsp;<span style="font-weight:400;font-size:0.82rem;opacity:0.75">(AI Voice Input)</span>';
            btn.disabled   = false;
            bar.classList.remove('visible');
        }
    }

    // ── Base64 helper ─────────────────────────────────────────────────────────
    function blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload  = () => resolve(reader.result.split(',')[1]);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    // ── Send to PHP proxy → Gemini ────────────────────────────────────────────
    async function sendToGemini(audioBlob, mimeType) {
        try {
            const base64Audio = await blobToBase64(audioBlob);

            const response = await fetch('gemini_voice.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ audio_base64: base64Audio, mime_type: mimeType })
            });

            const raw = await response.text();
            let parsed;
            try   { parsed = JSON.parse(raw); }
            catch { throw new Error('Server returned non-JSON: ' + raw.substring(0, 200)); }

            if (!response.ok || parsed.error) {
                throw new Error(parsed.error || 'Server error ' + response.status);
            }

            aiData = {
                transcript: parsed.transcript || '',
                sales:      (parsed.sales     || []).filter(s => s.quantity > 0),
                expenses:   (parsed.expenses  || []).filter(e => e.ingredient_name && String(e.ingredient_name).trim())
            };

            openAiModal();

        } catch (err) {
            alert('AI Voice Error:\n' + err.message);
        } finally {
            setButtonState('idle');
        }
    }

    // ── Modal open / close ────────────────────────────────────────────────────
    function openAiModal() {
        document.getElementById('ai-transcript').textContent =
            aiData.transcript ? '"' + aiData.transcript + '"' : '(No speech detected)';
        renderSalesRows();
        renderExpenseRows();
        document.getElementById('ai-overlay').classList.add('open');
    }

    window.closeAiModal = function () {
        document.getElementById('ai-overlay').classList.remove('open');
        // Reset confirm button in case it was mid-save
        const btn = document.getElementById('ai-confirm-btn');
        btn.disabled     = false;
        btn.textContent  = '✓ Confirm & Save';
    };

    window.handleOverlayClick = function (e) {
        if (e.target === document.getElementById('ai-overlay')) window.closeAiModal();
    };

    // ── Render sales rows ─────────────────────────────────────────────────────
    function renderSalesRows() {
        const container = document.getElementById('ai-sales-rows');
        container.innerHTML = '';

        if (!aiData.sales.length) {
            container.innerHTML = '<div class="ai-empty-msg">No sales items detected in the audio.</div>';
            return;
        }

        aiData.sales.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'ai-row sales-row';
            const opts = MENU_ITEMS.map(m =>
                `<option value="${esc(m)}" ${m === item.tea_type ? 'selected' : ''}>${esc(m)}</option>`
            ).join('');
            row.innerHTML = `
                <select class="ai-input" data-si="${idx}" data-f="tea_type">${opts}</select>
                <input  class="ai-input" type="number" min="1" value="${parseInt(item.quantity) || 1}"
                        data-si="${idx}" data-f="quantity" style="text-align:center">
                <button class="ai-remove-btn" onclick="removeSale(${idx})" title="Remove">✕</button>
            `;
            container.appendChild(row);
        });
    }

    window.removeSale = function (idx) {
        aiData.sales.splice(idx, 1);
        renderSalesRows();
    };

    // ── Render expense rows ───────────────────────────────────────────────────
    function renderExpenseRows() {
        const container = document.getElementById('ai-expense-rows');
        container.innerHTML = '';

        if (!aiData.expenses.length) {
            container.innerHTML = '<div class="ai-empty-msg">No expense items detected in the audio.</div>';
            return;
        }

        aiData.expenses.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'ai-row expense-row';
            row.innerHTML = `
                <input class="ai-input" type="text"   value="${esc(item.ingredient_name)}" placeholder="Ingredient"
                       data-ei="${idx}" data-f="ingredient_name">
                <input class="ai-input" type="text"   value="${esc(item.quantity)}"        placeholder="e.g. 2 kg"
                       data-ei="${idx}" data-f="quantity">
                <input class="ai-input" type="number" value="${parseFloat(item.amount)||0}" min="0" step="0.01"
                       data-ei="${idx}" data-f="amount" style="text-align:center">
                <button class="ai-remove-btn" onclick="removeExpense(${idx})" title="Remove">✕</button>
            `;
            container.appendChild(row);
        });
    }

    window.removeExpense = function (idx) {
        aiData.expenses.splice(idx, 1);
        renderExpenseRows();
    };

    // ── Collect current DOM values ────────────────────────────────────────────
    function collectData() {
        const salesMap = {};
        document.querySelectorAll('#ai-sales-rows [data-si]').forEach(el => {
            const i = el.dataset.si;
            if (!salesMap[i]) salesMap[i] = {};
            salesMap[i][el.dataset.f] = el.value;
        });

        const expMap = {};
        document.querySelectorAll('#ai-expense-rows [data-ei]').forEach(el => {
            const i = el.dataset.ei;
            if (!expMap[i]) expMap[i] = {};
            expMap[i][el.dataset.f] = el.value;
        });

        return {
            sales: Object.values(salesMap)
                .map(s => ({ tea_type: s.tea_type, quantity: parseInt(s.quantity) || 0 }))
                .filter(s => s.quantity > 0),
            expenses: Object.values(expMap)
                .map(e => ({ ingredient_name: e.ingredient_name.trim(), quantity: e.quantity.trim(), amount: parseFloat(e.amount) || 0 }))
                .filter(e => e.ingredient_name)
        };
    }

    // ── Confirm & Save ────────────────────────────────────────────────────────
    window.confirmAndSave = async function () {
        const confirmBtn = document.getElementById('ai-confirm-btn');
        confirmBtn.disabled    = true;
        confirmBtn.textContent = '⏳ Saving…';

        const payload = collectData();
        let savedSales = 0, savedExpenses = 0;

        try {
            // 1. Save sales — add quantity on top of today's running tally
            for (const sale of payload.sales) {
                await updateTeaCount(sale.tea_type, sale.quantity);
                savedSales++;
            }

            // 2. Save expenses — direct insert into outgoing_expenses
            if (typeof sbClient !== 'undefined' && sbClient) {
                for (const exp of payload.expenses) {
                    const { error } = await sbClient.from('outgoing_expenses').insert([{
                        phone_number:    USER_PHONE,
                        user_name:       USER_NAME,
                        ingredient_name: exp.ingredient_name,
                        quantity:        exp.quantity,
                        amount:          exp.amount
                    }]);
                    if (error) console.error('Expense insert error:', error.message);
                    else savedExpenses++;
                }
            } else if (payload.expenses.length > 0) {
                alert('Supabase client not available — sales saved but expenses could not be written.');
            }

            window.closeAiModal();

            // Refresh UI panels
            if (savedExpenses > 0 && typeof fetchOutgoingData === 'function') await fetchOutgoingData();
            if (savedSales    > 0 && typeof fetchInitialData  === 'function') await fetchInitialData();

            confirmBtn.disabled    = false;
            confirmBtn.textContent = '✓ Confirm & Save';

        } catch (err) {
            alert('Save failed: ' + err.message);
            confirmBtn.disabled    = false;
            confirmBtn.textContent = '✓ Confirm & Save';
        }
    };

    // ── Utility: HTML-escape for attribute values ─────────────────────────────
    function esc(str) {
        return String(str || '')
            .replace(/&/g,  '&amp;')
            .replace(/"/g,  '&quot;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;');
    }

})();
</script>

</body>
</html>
