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
    <title>Expenditure Dashboard - Invest</title>
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

        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; position: relative; z-index: 1; }

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

        .card {
            position: relative;
            background: linear-gradient(180deg, rgba(42, 28, 20, 0.72), rgba(27, 19, 14, 0.54));
            border-radius: 24px;
            border: 1px solid rgba(255,255,255,0.08);
            padding: 1.5rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 25px 55px rgba(20, 12, 7, 0.26), inset 0 1px 0 rgba(255,255,255,0.06), 0 0 24px rgba(215, 170, 118, 0.04);
            margin-bottom: 1.5rem;
        }
        .card h3 { color: var(--primary); border-bottom: 1px solid rgba(212, 166, 121, 0.26); padding-bottom: 0.6rem; margin-bottom: 1rem; font-size: 1.1rem; }

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
            margin-top: 0.5rem;
            box-shadow: 0 14px 26px rgba(184, 123, 66, 0.28);
            transition: filter 0.2s ease;
        }
        .submit-btn:hover { filter: brightness(1.04); }

        .display-list { list-style: none; margin-top: 1rem; }
        .display-list li {
            background: rgba(35, 24, 18, 0.5);
            padding: 0.9rem 1rem;
            border-radius: 14px;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(212, 166, 121, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            backdrop-filter: blur(8px);
        }
        .invest-details { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; flex: 1; }
        .invest-reason { font-weight: 700; color: #f8ead8; font-size: 1.05rem; word-break: break-word; }
        .invest-amount { font-size: 1rem; font-weight: 700; color: #f7d6b2; word-break: break-word; }
        .invest-meta { font-size: 0.75rem; color: var(--muted); word-break: break-word; }

        .delete-btn {
            background: linear-gradient(135deg, #f5b7a2, #c8645d);
            color: #1a110d;
            border: none;
            padding: 0.5rem 0.9rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(200, 100, 93, 0.3);
            transition: transform 0.2s ease, filter 0.2s ease;
            white-space: nowrap;
        }
        .delete-btn:hover {
            transform: scale(1.05);
            filter: brightness(1.08);
        }

        .total-box {
            margin-top: 1.2rem;
            padding: 1rem 1.2rem;
            background: linear-gradient(135deg, rgba(215, 170, 118, 0.18), rgba(184, 123, 66, 0.12));
            border: 1px dashed rgba(212, 166, 121, 0.48);
            border-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 1.15rem;
            color: #f7d6b2;
            box-shadow: 0 10px 25px rgba(184, 123, 66, 0.08);
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
            .card {
                padding: 1rem;
            }
            .display-list li {
                padding: 0.75rem 0.8rem;
                gap: 0.5rem;
            }
            .invest-reason {
                font-size: 0.95rem;
            }
            .invest-amount {
                font-size: 0.9rem;
            }
            .invest-meta {
                font-size: 0.7rem;
            }
            .delete-btn {
                padding: 0.45rem 0.7rem;
                font-size: 0.78rem;
            }
            .logout-btn {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                margin-left: 0;
            }
            .total-box {
                font-size: 1rem;
                padding: 0.9rem 1rem;
            }
        }
    </style>
</head>
<body>

<nav>
    <button id="btn-page1" onclick="window.location.href='everyone.php'">Everyone</button>
    <button id="btn-page2" onclick="window.location.href='individual.php'">Individual</button>
    <button id="btn-page3" onclick="window.location.href='modify.php'">Modify</button>
    <button id="btn-page4" onclick="window.location.href='due.php'">Due</button>
    <button id="btn-page5" class="active" onclick="window.location.href='invest.php'">Invest</button>
</nav>

<div class="container">
    <div class="app-header">
        <div>
            <h2>User: <span id="header-user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Member'); ?></span></h2>
            <p style="color: #e9d3ae;">Date: <span id="header-date"></span></p>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="card">
        <h3>Add New Investment</h3>
        <div class="form-group">
            <label>Note / Source</label>
            <input type="text" id="invest-reason-input" placeholder="e.g. Capital injection, Equipment fund">
        </div>
        <div class="form-group">
            <label>Amount (৳)</label>
            <input type="number" id="invest-amount-input" placeholder="e.g. 5000">
        </div>
        <button class="submit-btn" onclick="submitInvest()">Submit Investment</button>
    </div>

    <div class="card">
        <h3>Investment Ledger</h3>
        <ul id="invest-items-container" class="display-list">
            <!-- Investment items loaded dynamically -->
        </ul>
        <div class="total-box">
            <span>Total Investment:</span>
            <span id="total-invest-val">0 ৳</span>
        </div>
    </div>
</div>

<script>
    const SUPABASE_URL = "<?php echo defined('SUPABASE_URL') ? SUPABASE_URL : ''; ?>";
    const SUPABASE_KEY = "<?php echo defined('SUPABASE_KEY') ? SUPABASE_KEY : ''; ?>";
    const USER_PHONE = "<?php echo $_SESSION['user_phone'] ?? ''; ?>";
    const USER_NAME = "<?php echo $_SESSION['user_name'] ?? ''; ?>";

    document.getElementById('header-date').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    let sbClient = null;
    let globalInvestRecords = [];

    if (typeof supabase !== 'undefined' || typeof Supabase !== 'undefined') {
        const initializationEngine = typeof Supabase !== 'undefined' ? Supabase : supabase;
        if (SUPABASE_URL && SUPABASE_KEY) {
            try {
                sbClient = initializationEngine.createClient(SUPABASE_URL, SUPABASE_KEY);
            } catch (err) {
                console.error("Supabase client creation failed:", err);
            }
        }
    }

    async function fetchInvestRecords() {
        if (!sbClient) {
            const local = localStorage.getItem('local_invest_items');
            globalInvestRecords = local ? JSON.parse(local) : [];
            renderInvestList();
            return;
        }

        try {
            let { data, error } = await sbClient.from('investments').select('*').order('created_at', { ascending: false });
            if (error) {
                console.error("Error fetching investments:", error.message);
                const local = localStorage.getItem('local_invest_items');
                globalInvestRecords = local ? JSON.parse(local) : [];
            } else {
                globalInvestRecords = data || [];
            }
            renderInvestList();
        } catch (err) {
            console.error("Unexpected error fetching investments:", err);
            renderInvestList();
        }
    }

    async function submitInvest() {
        const reasonInput = document.getElementById('invest-reason-input');
        const amountInput = document.getElementById('invest-amount-input');

        const reason = reasonInput.value.trim();
        const amount = parseFloat(amountInput.value);

        if (!reason || isNaN(amount) || amount <= 0) {
            alert('Please enter a valid note and amount.');
            return;
        }

        const newItem = {
            reason: reason,
            amount: amount,
            phone_number: USER_PHONE,
            user_name: USER_NAME,
            created_at: new Date().toISOString()
        };

        if (sbClient) {
            try {
                let { data, error } = await sbClient.from('investments').insert([newItem]).select();
                if (error) {
                    console.error("Supabase insert error:", error.message);
                    newItem.id = 'local_' + Date.now();
                    globalInvestRecords.unshift(newItem);
                    localStorage.setItem('local_invest_items', JSON.stringify(globalInvestRecords));
                } else if (data && data.length > 0) {
                    globalInvestRecords.unshift(data[0]);
                } else {
                    await fetchInvestRecords();
                }
            } catch (err) {
                console.error("Exception during submit:", err);
                newItem.id = 'local_' + Date.now();
                globalInvestRecords.unshift(newItem);
                localStorage.setItem('local_invest_items', JSON.stringify(globalInvestRecords));
            }
        } else {
            newItem.id = 'local_' + Date.now();
            globalInvestRecords.unshift(newItem);
            localStorage.setItem('local_invest_items', JSON.stringify(globalInvestRecords));
        }

        reasonInput.value = '';
        amountInput.value = '';
        renderInvestList();
    }

    async function completeInvestItem(id) {
        if (sbClient && !String(id).startsWith('local_')) {
            try {
                let { error } = await sbClient.from('investments').delete().eq('id', id);
                if (error) {
                    console.error("Error deleting investment item:", error.message);
                }
            } catch (err) {
                console.error("Exception deleting investment item:", err);
            }
        }

        globalInvestRecords = globalInvestRecords.filter(item => item.id !== id);
        localStorage.setItem('local_invest_items', JSON.stringify(globalInvestRecords));

        renderInvestList();
    }

    function renderInvestList() {
        const container = document.getElementById('invest-items-container');
        const totalValElem = document.getElementById('total-invest-val');
        container.innerHTML = '';

        let totalInvest = 0;

        if (globalInvestRecords.length === 0) {
            container.innerHTML = '<li style="justify-content: center; color: var(--muted); padding: 1.2rem;">No investments recorded.</li>';
            totalValElem.textContent = '0 ৳';
            return;
        }

        globalInvestRecords.forEach(item => {
            const amt = parseFloat(item.amount) || 0;
            totalInvest += amt;

            const li = document.createElement('li');
            li.innerHTML = `
                <div class="invest-details">
                    <span class="invest-reason">${escapeHtml(item.reason)}</span>
                    <span class="invest-amount">${amt.toFixed(2)} ৳</span>
                    <span class="invest-meta">Invested by ${escapeHtml(item.user_name || 'Member')}</span>
                </div>
                <button class="delete-btn" title="Delete Investment" onclick="completeInvestItem('${item.id}')">Delete</button>
            `;
            container.appendChild(li);
        });

        totalValElem.textContent = totalInvest.toFixed(2) + ' ৳';
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (sbClient) {
        sbClient
            .channel('public:investments')
            .on('postgres_changes', { event: '*', schema: 'public', table: 'investments' }, () => {
                fetchInvestRecords();
            })
            .subscribe();
    }

    fetchInvestRecords();
</script>
</body>
</html>
