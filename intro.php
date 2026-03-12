<?php
// Intro loading screen — entry point
// After animation, redirects to mode selection (index.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Knowledge Gateway</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,300;0,8..60,400;1,8..60,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f0f0f;
            --red: #c0392b;
            --red-bright: #e74c3c;
            --text: #e8e8e8;
            --text-muted: #777;
            --card: #1a1a1a;
            --border: #2a2a2a;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* ── Background particle lines ── */
        .bg-lines {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .bg-lines span {
            position: absolute;
            width: 1px;
            background: linear-gradient(to bottom, transparent, rgba(192,57,43,0.15), transparent);
            animation: lineFlow 6s linear infinite;
        }

        @keyframes lineFlow {
            from { transform: translateY(-100vh); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            to   { transform: translateY(100vh);  opacity: 0; }
        }

        /* ── Main stage ── */
        .intro-stage {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        /* ── Logo ── */
        .logo-wrap {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            opacity: 0;
            animation: logoAppear 0.8s ease-out 0.4s forwards;
        }
        .logo-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--red) 0%, #8b1c0d 100%);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 40px rgba(192, 57, 43, 0.4), 0 0 80px rgba(192, 57, 43, 0.15);
        }
        .logo-icon svg { width: 50px; height: 50px; }
        .logo-name {
            margin-top: 16px;
            font-family: 'Source Serif 4', serif;
            font-size: 2.4em;
            font-weight: 400;
            letter-spacing: -0.03em;
            color: var(--text);
        }
        .logo-name span { color: var(--red-bright); }
        .logo-sub {
            font-size: 0.82em;
            color: var(--text-muted);
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin-top: 4px;
        }

        @keyframes logoAppear {
            from { opacity: 0; transform: scale(0.8); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* ── Topic bubbles ── */
        .bubbles-container {
            position: absolute;
            inset: -140px;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            padding: 9px 18px;
            border-radius: 30px;
            font-size: 0.8em;
            font-weight: 600;
            letter-spacing: 0.03em;
            opacity: 0;
            border: 1px solid;
            white-space: nowrap;
            transform: scale(0.4);
            animation: bubblePop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Different coloured bubbles */
        .bubble.red   { background: rgba(192,57,43,0.18); border-color: rgba(192,57,43,0.5);  color: #e05a4b; }
        .bubble.grey  { background: rgba(80,80,80,0.2);   border-color: rgba(100,100,100,0.4); color: #aaa; }
        .bubble.dark  { background: rgba(40,40,40,0.6);   border-color: rgba(60,60,60,0.6);    color: #888; }

        @keyframes bubblePop {
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes bubbleFloat {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }

        /* ── Tagline ── */
        .tagline {
            font-family: 'Source Serif 4', serif;
            font-size: 1.25em;
            font-weight: 300;
            font-style: italic;
            color: var(--text-muted);
            text-align: center;
            opacity: 0;
            animation: taglineReveal 1s ease-out 3.2s forwards;
            margin-top: 10px;
            letter-spacing: 0.01em;
        }

        @keyframes taglineReveal {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Progress bar ── */
        .progress-bar-wrap {
            width: 200px;
            height: 2px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 32px;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.5s ease 0.8s forwards;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--red-bright), var(--red));
            border-radius: 2px;
            animation: progressFill 4s ease-in-out 0.8s forwards;
        }
        @keyframes progressFill {
            from { width: 0%; }
            to   { width: 100%; }
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* ── Page out ── */
        .page-out {
            animation: pageOut 0.55s ease-in forwards;
        }
        @keyframes pageOut {
            to { opacity: 0; transform: scale(1.04); }
        }
    </style>
</head>
<body>

    <!-- Background animated lines -->
    <div class="bg-lines" id="bgLines"></div>

    <!-- Main intro stage -->
    <div class="intro-stage" id="introStage">

        <!-- Floating topic bubbles (around logo) -->
        <div class="bubbles-container" id="bubblesContainer"></div>

        <!-- Logo -->
        <div class="logo-wrap">
            <div class="logo-icon">
                <svg viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M25 8L8 17V25C8 34.4 15.6 43.2 25 46C34.4 43.2 42 34.4 42 25V17L25 8Z" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
                    <path d="M17 25L22 30L33 20" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="logo-name">Knowledge <span>Gateway</span></div>
            <div class="logo-sub">Quiz &amp; Learning Platform</div>
        </div>

        <!-- Tagline -->
        <div class="tagline">"One platform for better understanding."</div>

        <!-- Progress -->
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill"></div>
        </div>
    </div>

<script>
    // ── Topic bubbles config ──
    const topics = [
        { label: 'Python',        cls: 'red',  top: '15%',  left: '8%',  delay: 0.8  },
        { label: 'PHP',           cls: 'grey', top: '25%',  left: '78%', delay: 1.0  },
        { label: 'Java',          cls: 'dark', top: '68%',  left: '6%',  delay: 1.2  },
        { label: 'Class Test',    cls: 'red',  top: '72%',  left: '72%', delay: 1.4  },
        { label: 'Practice Test', cls: 'grey', top: '10%',  left: '58%', delay: 1.6  },
        { label: 'JavaScript',    cls: 'dark', top: '80%',  left: '38%', delay: 1.8  },
        { label: 'Databases',     cls: 'grey', top: '18%',  left: '30%', delay: 2.0  },
        { label: 'More topics…',  cls: 'dark', top: '58%',  left: '83%', delay: 2.2  },
        { label: 'Algorithms',    cls: 'red',  top: '45%',  left: '4%',  delay: 2.4  },
        { label: 'Networks',      cls: 'grey', top: '85%',  left: '55%', delay: 2.0  },
    ];

    const container = document.getElementById('bubblesContainer');

    topics.forEach(t => {
        const el = document.createElement('div');
        el.className = `bubble ${t.cls}`;
        el.textContent = t.label;
        el.style.top = t.top;
        el.style.left = t.left;
        el.style.animationDelay = t.delay + 's';
        // After popping in, make them float
        el.addEventListener('animationend', () => {
            el.style.animation = `bubbleFloat ${2.8 + Math.random()}s ease-in-out infinite`;
            el.style.animationDelay = Math.random() + 's';
        });
        container.appendChild(el);
    });

    // ── Background lines ──
    const bgLines = document.getElementById('bgLines');
    for (let i = 0; i < 14; i++) {
        const ln = document.createElement('span');
        ln.style.left = (Math.random() * 100) + '%';
        ln.style.height = (80 + Math.random() * 200) + 'px';
        ln.style.animationDelay = (Math.random() * 6) + 's';
        ln.style.animationDuration = (4 + Math.random() * 4) + 's';
        bgLines.appendChild(ln);
    }

    // ── Redirect after animation ──
    setTimeout(() => {
        document.getElementById('introStage').classList.add('page-out');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 550);
    }, 5000); // 5 second total intro
</script>
</body>
</html>
