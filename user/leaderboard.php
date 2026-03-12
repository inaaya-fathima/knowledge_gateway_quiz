<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$myId   = (int)$_SESSION['user_id'];
$myName = $_SESSION['user_name'] ?? 'You';

// ── Build leaderboard from users.json star counts + users table names ──
$allUsers = get_all_users();
$board    = [];

foreach ($allUsers['users'] as $u) {
    if (!isset($u['db_id'])) continue;
    $board[] = [
        'db_id'   => (int)$u['db_id'],
        'name'    => $u['name'] ?? $u['username'],
        'stars'   => (int)($u['stars'] ?? 0),
        'streak'  => (int)($u['streak_days'] ?? 0),
        'is_me'   => ((int)$u['db_id'] === $myId),
    ];
}

// Sort by stars DESC, then streak DESC
usort($board, fn($a, $b) =>
    $b['stars'] <=> $a['stars'] ?: $b['streak'] <=> $a['streak']
);

// Find own rank
$myRank = 0;
foreach ($board as $i => $entry) {
    if ($entry['is_me']) { $myRank = $i + 1; break; }
}

// Pull each user's recent quiz attempt count
$attemptMap = [];
try {
    $rows = $pdo->query("SELECT user_id, COUNT(*) as cnt FROM quiz_results GROUP BY user_id")->fetchAll();
    foreach ($rows as $r) $attemptMap[(int)$r['user_id']] = (int)$r['cnt'];
    $rows2 = $pdo->query("SELECT user_id, COUNT(*) as cnt FROM test_results WHERE user_id IS NOT NULL GROUP BY user_id")->fetchAll();
    foreach ($rows2 as $r) {
        $uid = (int)$r['user_id'];
        $attemptMap[$uid] = ($attemptMap[$uid] ?? 0) + (int)$r['cnt'];
    }
} catch (PDOException $e) { $attemptMap = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — Knowledge Gateway</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-wrap { max-width: 760px; margin: 30px auto; padding: 0 20px; }

        /* ── Podium (top 3) ── */
        .podium {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 12px;
            margin-bottom: 36px;
            padding: 10px 0;
        }
        .podium-slot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
            max-width: 180px;
        }
        .podium-slot .avatar {
            width: 56px; height: 56px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5em;
            font-weight: 700;
            border: 3px solid transparent;
            flex-shrink: 0;
        }
        .podium-slot.rank-1 .avatar { background: linear-gradient(135deg,#b8860b,#ffd700); border-color: #ffd700; color: #000; }
        .podium-slot.rank-2 .avatar { background: linear-gradient(135deg,#666,#bbb); border-color: #bbb; color: #fff; }
        .podium-slot.rank-3 .avatar { background: linear-gradient(135deg,#7b4226,#cd7f32); border-color: #cd7f32; color: #fff; }
        .podium-slot .p-name { font-size: 0.85em; font-weight: 600; text-align: center; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .podium-slot .p-stars { font-size: 0.8em; color: #ffc107; font-weight: 700; }
        .podium-block {
            width: 100%;
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4em; font-weight: 700; color: rgba(255,255,255,0.7);
            padding: 10px 0;
            border: 1px solid var(--border);
            border-bottom: none;
        }
        .podium-slot.rank-1 .podium-block { height: 90px; background: linear-gradient(180deg, rgba(255,215,0,0.15), transparent); border-color: rgba(255,215,0,0.3); }
        .podium-slot.rank-2 .podium-block { height: 64px; background: linear-gradient(180deg, rgba(192,192,192,0.1), transparent); border-color: rgba(192,192,192,0.2); }
        .podium-slot.rank-3 .podium-block { height: 48px; background: linear-gradient(180deg, rgba(205,127,50,0.1), transparent); border-color: rgba(205,127,50,0.2); }

        /* ── Full table ── */
        .rank-table { width: 100%; border-collapse: collapse; }
        .rank-row {
            display: grid;
            grid-template-columns: 44px 1fr 80px 70px 60px;
            align-items: center;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 6px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            transition: background var(--transition), border-color var(--transition);
        }
        .rank-row:hover { background: var(--card-hover); border-color: var(--border-hover); }
        .rank-row.mine {
            background: rgba(192,57,43,0.08);
            border-color: var(--red-dim);
        }
        .rank-num { font-size: 0.9em; font-weight: 700; color: var(--text-muted); text-align: center; }
        .rank-num.top1 { color: #ffd700; }
        .rank-num.top2 { color: #bbb; }
        .rank-num.top3 { color: #cd7f32; }
        .rank-name { font-size: 0.92em; font-weight: 500; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rank-name .you-badge { font-size: 0.68em; background: var(--red-glow); color: var(--red-bright); border: 1px solid var(--red-dim); border-radius: 10px; padding: 1px 7px; margin-left: 7px; vertical-align: middle; }
        .rank-stars { font-size: 0.85em; font-weight: 700; color: #ffc107; text-align: center; }
        .rank-streak { font-size: 0.8em; color: var(--text-muted); text-align: center; }
        .rank-attempts { font-size: 0.8em; color: var(--text-faint); text-align: center; }

        .rank-header { display: grid; grid-template-columns: 44px 1fr 80px 70px 60px; padding: 6px 16px; margin-bottom: 8px; }
        .rank-header span { font-size: 0.72em; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.07em; text-align: center; }
        .rank-header span:nth-child(2) { text-align: left; }

        .my-rank-banner {
            background: rgba(192,57,43,0.1);
            border: 1px solid var(--red-dim);
            border-radius: var(--radius);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }
        .my-rank-banner .big-num { font-size: 2.8em; font-weight: 700; color: var(--red-bright); line-height: 1; font-family: 'Inter', sans-serif; }
        .my-rank-banner p { margin: 0; font-size: 0.85em; color: var(--text-muted); }
        .my-rank-banner strong { color: var(--text); }

        .empty-board { text-align: center; padding: 60px 20px; color: var(--text-muted); }
    </style>
</head>
<body class="bg-gradient">
<div class="page-wrap">

    <div class="header-bar">
        <h2>🏆 Leaderboard</h2>
        <a href="dashboard.php" class="back-link">← Hub</a>
    </div>

    <!-- My rank banner -->
    <?php if ($myRank > 0): ?>
    <div class="my-rank-banner">
        <div class="big-num">#<?php echo $myRank; ?></div>
        <div>
            <strong><?php echo htmlspecialchars($myName); ?></strong>
            <p>Your current position — keep going to climb higher! ⭐ <?php echo ($board[$myRank-1]['stars'] ?? 0); ?> stars &nbsp;·&nbsp; 🔥 <?php echo ($board[$myRank-1]['streak'] ?? 0); ?> day streak</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($board)): ?>
        <div class="empty-board">
            <div style="font-size:3em; opacity:.3; margin-bottom:14px;">🏅</div>
            <p>No students on the board yet. Be the first to earn stars!</p>
        </div>
    <?php else: ?>

        <!-- ── Podium (top 3) ── -->
        <?php if (count($board) >= 2):
            // Reorder: 2nd | 1st | 3rd
            $podiumOrder = [];
            if (count($board) >= 2) $podiumOrder[] = ['slot' => 2, 'data' => $board[1] ?? null];
            if (count($board) >= 1) $podiumOrder[] = ['slot' => 1, 'data' => $board[0]];
            if (count($board) >= 3) $podiumOrder[] = ['slot' => 3, 'data' => $board[2] ?? null];
        ?>
        <div class="podium">
            <?php foreach ($podiumOrder as $p):
                if (!$p['data']) continue;
                $initials = strtoupper(substr($p['data']['name'], 0, 1));
                $medals   = ['', '🥇', '🥈', '🥉'];
            ?>
                <div class="podium-slot rank-<?php echo $p['slot']; ?>">
                    <div class="avatar"><?php echo $initials; ?></div>
                    <div class="p-name" title="<?php echo htmlspecialchars($p['data']['name']); ?>">
                        <?php echo htmlspecialchars($p['data']['name']); ?>
                        <?php if ($p['data']['is_me']): ?> <span style="color:var(--red-bright); font-size:0.7em;">(you)</span><?php endif; ?>
                    </div>
                    <div class="p-stars">⭐ <?php echo $p['data']['stars']; ?></div>
                    <div class="podium-block"><?php echo $medals[$p['slot']]; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── Full Rankings ── -->
        <div class="section-title" style="margin-bottom:8px;">All Rankings</div>

        <div class="rank-header">
            <span>Rank</span>
            <span>Student</span>
            <span>⭐ Stars</span>
            <span>🔥 Streak</span>
            <span>Quizzes</span>
        </div>

        <?php foreach ($board as $i => $entry):
            $rank     = $i + 1;
            $attempts = $attemptMap[$entry['db_id']] ?? 0;
            $numClass = $rank === 1 ? 'top1' : ($rank === 2 ? 'top2' : ($rank === 3 ? 'top3' : ''));
        ?>
            <div class="rank-row <?php echo $entry['is_me'] ? 'mine' : ''; ?>">
                <div class="rank-num <?php echo $numClass; ?>">
                    <?php
                    if ($rank <= 3) echo ['','🥇','🥈','🥉'][$rank];
                    else echo "#$rank";
                    ?>
                </div>
                <div class="rank-name">
                    <?php echo htmlspecialchars($entry['name']); ?>
                    <?php if ($entry['is_me']): ?>
                        <span class="you-badge">YOU</span>
                    <?php endif; ?>
                </div>
                <div class="rank-stars">⭐ <?php echo $entry['stars']; ?></div>
                <div class="rank-streak">🔥 <?php echo $entry['streak']; ?>d</div>
                <div class="rank-attempts"><?php echo $attempts; ?></div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>
</body>
</html>
