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
    <title>Expenditure Dashboard - Individual</title>
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
            inset: -18% auto auto -12%;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(215, 170, 118, 0.14), transparent 70%);
            filter: blur(34px);
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

        .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
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

        .display-list { list-style: none; }
        .display-list li {
            background: rgba(35, 24, 18, 0.5);
            padding: 0.8rem 0.9rem;
            border-radius: 14px;
            margin-bottom: 0.6rem;
            border: 1px solid rgba(212, 166, 121, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(8px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .display-list li:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 25px rgba(20, 12, 7, 0.16);
        }
        .display-list li span { word-break: break-word; }
        .total-box {
            margin-top: 1rem;
            padding: 0.9rem 1rem;
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.18), rgba(184, 123, 66, 0.12));
            border: 1px dashed rgba(212, 166, 121, 0.48);
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 1.05rem;
            color: #f7d6b2;
            box-shadow: 0 10px 25px rgba(184, 123, 66, 0.08);
            word-break: break-word;
        }

        .date-group-box {
            background: linear-gradient(180deg, rgba(42, 28, 20, 0.72), rgba(27, 19, 14, 0.46));
            border: 1px solid rgba(212,166,121,0.2);
            border-radius: 20px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 12px 28px rgba(20,12,7,0.14), 0 0 24px rgba(215,170,118,0.04);
        }
        .date-group-title {
            font-size: 1.1rem;
            color: #f7d6b2;
            font-weight: 800;
            margin-bottom: 0.8rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px dashed rgba(212, 166, 121, 0.25);
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
            .columns { grid-template-columns: 1fr; gap: 1.25rem; }
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
            .col-card,
            .date-group-box {
                padding: 1rem;
            }
            .display-list li,
            .total-box {
                font-size: 0.85rem;
            }
            .logout-btn {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<nav>
    <button id="btn-page1" onclick="window.location.href='everyone.php'">Everyone</button>
    <button id="btn-page2" class="active" onclick="window.location.href='individual.php'">Individual</button>
    <button id="btn-page3" onclick="window.location.href='modify.php'">Modify</button>
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

    <div id="page2" class="page active">
        <div class="columns">
            <div class="col-card">
                <h3 style="color: #d7aa76;">Your Personal Incoming (History logs)</h3>
                <div id="p2-incoming-container"></div>
            </div>
            <div class="col-card">
                <h3 style="color: #d7aa76;">Your Personal Outgoing</h3>
                <ul id="p2-outgoing-list" class="display-list"></ul>
                <div class="total-box"><span>Your Total Outgoing:</span><span id="p2-total-val">0 ৳</span></div>
            </div>
        </div>
    </div>
</div>

<script>
    const SUPABASE_URL = "<?php echo defined('SUPABASE_URL') ? SUPABASE_URL : ''; ?>";
    const SUPABASE_KEY = "<?php echo defined('SUPABASE_KEY') ? SUPABASE_KEY : ''; ?>";
    const USER_PHONE = "<?php echo $_SESSION['user_phone'] ?? ''; ?>";

    document.getElementById('header-date').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    let sbClient = null;
    let globalTeaRecords = [];
    let globalOutgoingRecords = [];

    if (typeof supabase !== 'undefined' || typeof Supabase !== 'undefined') {
        const engine = typeof Supabase !== 'undefined' ? Supabase : supabase;
        if (SUPABASE_URL && SUPABASE_KEY) {
            try {
                sbClient = engine.createClient(SUPABASE_URL, SUPABASE_KEY);
            } catch (err) {
                console.error("Supabase engine fallback initialization dropped:", err);
            }
        }
    }

    async function fetchInitialData() {
        if (!sbClient) return;
        try {
            // Read historically persistent datasets
            let { data: tea, error: e1 } = await sbClient.from('incoming_tea').select('*');
            let { data: outgoing, error: e2 } = await sbClient.from('outgoing_expenses').select('*');
            
            if (e1) console.error("Error fetching tea metrics:", e1.message);
            if (e2) console.error("Error fetching outgoing metrics:", e2.message);
            
            globalTeaRecords = tea || [];
            globalOutgoingRecords = outgoing || [];
            
            renderInterface();
        } catch(err) {
            console.error("Data tracking operations crashed unexpectedly:", err);
        }
    }

    function renderInterface() {
        const p2InContainer = document.getElementById('p2-incoming-container');
        p2InContainer.innerHTML = '';
        
        // Structure: { "YYYY-MM-DD": [ {tea_type, quantity} ] }
        const personalTimeline = {};

        globalTeaRecords.forEach(t => {
            if (t.quantity > 0 && t.phone_number === USER_PHONE) {
                let rawDate = t.created_at || new Date().toISOString();
                let dateKey = rawDate.split('T')[0];
                
                if (!personalTimeline[dateKey]) {
                    personalTimeline[dateKey] = [];
                }
                personalTimeline[dateKey].push({
                    tea_type: t.tea_type,
                    quantity: t.quantity
                });
            }
        });

        const sortedDates = Object.keys(personalTimeline).sort((a, b) => b.localeCompare(a));

        if (sortedDates.length === 0) {
            p2InContainer.innerHTML = '<div style="padding:0.75rem; background:rgba(27,19,14,0.7); border-radius:6px; border:1px solid rgba(212,166,121,0.35); color:#f8ead8;">No personal items matching history data.</div>';
        } else {
            sortedDates.forEach(date => {
                const formattedDisplayDate = new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                let dateBlockHtml = `
                    <div class="date-group-box">
                        <div class="date-group-title">📅 ${formattedDisplayDate}</div>
                        <ul class="display-list">
                `;
                
                personalTimeline[date].forEach(item => {
                    const teaName = item.tea_type;
                    const unitLabel = (teaName.includes("Pora Ruti") || teaName.includes("Singara") || teaName.includes("Dudh Danish")) ? "pcs" : "cups";
                    dateBlockHtml += `<li><span>${teaName}</span><strong>${item.quantity} ${unitLabel}</strong></li>`;
                });

                dateBlockHtml += `
                        </ul>
                    </div>
                `;
                p2InContainer.innerHTML += dateBlockHtml;
            });
        }

        const p2Out = document.getElementById('p2-outgoing-list');
        p2Out.innerHTML = '';
        let personalTotal = 0;
        
        globalOutgoingRecords.forEach(o => {
            if(o.phone_number === USER_PHONE) {
                personalTotal += parseFloat(o.amount || 0);
                p2Out.innerHTML += `<li><span>${o.ingredient_name} (${o.quantity})</span><strong>${o.amount} ৳</strong></li>`;
            }
        });
        document.getElementById('p2-total-val').textContent = personalTotal + ' ৳';
    }

    if (sbClient) {
        sbClient
            .channel('db-expenditures')
            .on('postgres_changes', { event: '*', schema: 'public', table: 'incoming_tea' }, () => { fetchInitialData(); })
            .on('postgres_changes', { event: '*', schema: 'public', table: 'outgoing_expenses' }, () => { fetchInitialData(); })
            .subscribe();

        fetchInitialData();
    }
</script>
</body>
</html>
