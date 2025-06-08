<?php
session_start();

// データベース設定（実際の環境に合わせて変更してください）
$db_config = [
    'host' => 'localhost',
    'dbname' => 'job_matching',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8", 
                   $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // データベース接続エラーの場合、デモモードで動作
    $demo_mode = true;
}

// デモデータ
$demo_jobs = [
    [
        'id' => 1,
        'title' => 'Webサイト開発',
        'company' => '株式会社テックイノベーション',
        'location' => '東京都渋谷区',
        'salary_min' => 300000,
        'salary_max' => 500000,
        'type' => 'フリーランス',
        'skills' => 'React,Node.js,JavaScript',
        'description' => 'ECサイトのフロントエンド開発を担当していただきます。',
        'rating' => 4.8,
        'reviews' => 24,
        'urgent' => 1,
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'id' => 2,
        'title' => 'モバイルアプリ開発',
        'company' => 'スタートアップABC',
        'location' => '大阪府大阪市',
        'salary_min' => 400000,
        'salary_max' => 600000,
        'type' => '契約社員',
        'skills' => 'Flutter,Dart,Firebase',
        'description' => '新しいソーシャルアプリの開発チームに参加してください。',
        'rating' => 4.6,
        'reviews' => 18,
        'urgent' => 0,
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
    ],
    [
        'id' => 3,
        'title' => 'データ分析',
        'company' => 'マーケティングプロ',
        'location' => 'リモート',
        'salary_min' => 250000,
        'salary_max' => 400000,
        'type' => 'プロジェクトベース',
        'skills' => 'Python,SQL,Tableau',
        'description' => '顧客データの分析とレポート作成をお願いします。',
        'rating' => 4.9,
        'reviews' => 31,
        'urgent' => 0,
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
    ]
];

$demo_freelancers = [
    [
        'id' => 1,
        'name' => 'Alex Johnson',
        'nationality' => 'アメリカ',
        'skills' => 'React,Node.js,Python',
        'rating' => 4.9,
        'reviews' => 47,
        'hourly_rate' => 4000,
        'availability' => '即日可能',
        'languages' => '英語,日本語',
        'experience' => 5
    ],
    [
        'id' => 2,
        'name' => 'Maria Santos',
        'nationality' => 'ブラジル',
        'skills' => 'Flutter,Dart,UI/UX',
        'rating' => 4.8,
        'reviews' => 32,
        'hourly_rate' => 3500,
        'availability' => '来週から',
        'languages' => 'ポルトガル語,英語,日本語',
        'experience' => 3
    ]
];

// ルーティング
$page = $_GET['page'] ?? 'login';
$action = $_POST['action'] ?? '';

// アクション処理
if ($action) {
    switch ($action) {
        case 'login':
            $user_type = $_POST['user_type'] ?? 'freelancer';
            $name = $_POST['name'] ?? 'テストユーザー';
            $_SESSION['user'] = [
                'id' => 1,
                'name' => $name,
                'type' => $user_type,
                'logged_in' => true
            ];
            $_SESSION['applications'] = $_SESSION['applications'] ?? [];
            $_SESSION['favorites'] = $_SESSION['favorites'] ?? [];
            $_SESSION['messages'] = $_SESSION['messages'] ?? [];
            header('Location: ?page=jobs');
            exit;
            break;
            
        case 'logout':
            session_destroy();
            header('Location: ?page=login');
            exit;
            break;
            
        case 'apply':
            if (isset($_SESSION['user'])) {
                $job_id = $_POST['job_id'];
                $job = array_filter($demo_jobs, function($j) use ($job_id) {
                    return $j['id'] == $job_id;
                });
                $job = reset($job);
                
                $_SESSION['applications'][] = [
                    'id' => count($_SESSION['applications']) + 1,
                    'job' => $job,
                    'status' => 'applied',
                    'applied_at' => date('Y-m-d H:i:s'),
                    'message' => $job['title'] . 'に応募しました。'
                ];
                $_SESSION['success_message'] = '応募が完了しました！';
            }
            header('Location: ?page=jobs');
            exit;
            break;
            
        case 'toggle_favorite':
            if (isset($_SESSION['user'])) {
                $item_id = $_POST['item_id'];
                $item_type = $_POST['item_type'];
                
                $favorite_key = $item_type . '_' . $item_id;
                
                if (isset($_SESSION['favorites'][$favorite_key])) {
                    unset($_SESSION['favorites'][$favorite_key]);
                } else {
                    if ($item_type === 'job') {
                        $item = array_filter($demo_jobs, function($j) use ($item_id) {
                            return $j['id'] == $item_id;
                        });
                    } else {
                        $item = array_filter($demo_freelancers, function($f) use ($item_id) {
                            return $f['id'] == $item_id;
                        });
                    }
                    $item = reset($item);
                    $_SESSION['favorites'][$favorite_key] = [
                        'item' => $item,
                        'type' => $item_type,
                        'added_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
            break;
            
        case 'send_message':
            if (isset($_SESSION['user'])) {
                $message = $_POST['message'] ?? '';
                if (!empty($message)) {
                    $_SESSION['messages'][] = [
                        'text' => $message,
                        'sender' => 'user',
                        'time' => date('H:i'),
                        'name' => $_SESSION['user']['name']
                    ];
                    
                    // 自動返信のシミュレーション
                    $_SESSION['messages'][] = [
                        'text' => 'ありがとうございます！詳細についてお話しできればと思います。',
                        'sender' => 'other',
                        'time' => date('H:i'),
                        'name' => 'Maria Santos'
                    ];
                }
            }
            header('Location: ?page=messages');
            exit;
            break;
    }
}

// ヘルパー関数
function isLoggedIn() {
    return isset($_SESSION['user']) && $_SESSION['user']['logged_in'];
}

function isFavorite($item_id, $type) {
    $favorite_key = $type . '_' . $item_id;
    return isset($_SESSION['favorites'][$favorite_key]);
}

function formatSalary($min, $max) {
    return '¥' . number_format($min) . ' - ¥' . number_format($max);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return $time . '秒前';
    if ($time < 3600) return floor($time/60) . '分前';
    if ($time < 86400) return floor($time/3600) . '時間前';
    if ($time < 2592000) return floor($time/86400) . '日前';
    return floor($time/2592000) . 'ヶ月前';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Work Hub - 外国人向け仕事マッチング</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">

<?php if (!isLoggedIn()): ?>
    <!-- ログイン画面 -->
    <div class="min-h-screen gradient-bg flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center mb-8">
                <i class="fas fa-globe text-blue-600 text-6xl mb-4"></i>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Global Work Hub</h1>
                <p class="text-gray-600">外国人向け仕事マッチングプラットフォーム</p>
            </div>
            
            <?php if (isset($_GET['demo'])): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ユーザータイプ</label>
                    <select name="user_type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="freelancer">フリーランサー</option>
                        <option value="company">企業担当者</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">名前</label>
                    <input type="text" name="name" value="Alex Johnson" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-bold transition-colors">
                    ログイン
                </button>
            </form>
            <?php else: ?>
            <a href="?demo=1" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-bold text-center transition-colors mb-4">
                <i class="fab fa-facebook mr-2"></i>
                Facebookで続ける
            </a>
            
            <div class="text-center">
                <p class="text-sm text-gray-500">または</p>
            </div>
            
            <a href="?demo=1" class="block w-full mt-4 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-lg font-medium text-center transition-colors">
                デモアカウントでログイン
            </a>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    ログインすることで、利用規約とプライバシーポリシーに同意したものとみなされます。
                </p>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- メインアプリケーション -->
    <!-- ヘッダー -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <i class="fas fa-globe text-blue-600 text-2xl"></i>
                    <h1 class="text-2xl font-bold text-gray-900">Global Work Hub</h1>
                </div>
                
                <nav class="hidden md:flex items-center gap-6">
                    <a href="?page=jobs" class="font-medium <?= $page === 'jobs' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">仕事を探す</a>
                    <a href="?page=freelancers" class="font-medium <?= $page === 'freelancers' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">人材を探す</a>
                    <a href="?page=messages" class="font-medium <?= $page === 'messages' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">メッセージ</a>
                    <a href="?page=applications" class="font-medium <?= $page === 'applications' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">応募履歴</a>
                    <a href="?page=favorites" class="font-medium <?= $page === 'favorites' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900' ?>">お気に入り</a>
                </nav>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <i class="fas fa-bell text-gray-600 hover:text-gray-900 cursor-pointer"></i>
                        <?php if (!empty($_SESSION['applications'])): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                            <?= count($_SESSION['applications']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                            <?= substr($_SESSION['user']['name'], 0, 1) ?>
                        </div>
                        <span class="hidden md:block text-sm font-medium text-gray-700"><?= htmlspecialchars($_SESSION['user']['name']) ?></span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 hidden md:block">
                                ログアウト
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 成功メッセージ -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- メインコンテンツ -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($page === 'jobs'): ?>
            <!-- 仕事一覧ページ -->
            <div class="mb-8">
                <div class="flex flex-col lg:flex-row gap-4 mb-6">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" placeholder="仕事を検索..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg flex items-center gap-2 transition-colors">
                        <i class="fas fa-filter"></i>
                        フィルター
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($demo_jobs as $job): ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($job['title']) ?></h3>
                                <?php if ($job['urgent']): ?>
                                <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs">急募</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-gray-600 mb-2"><?= htmlspecialchars($job['company']) ?></p>
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($job['location']) ?>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-calendar"></i>
                                    <?= timeAgo($job['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-1 mb-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span class="text-sm font-medium"><?= $job['rating'] ?></span>
                                <span class="text-xs text-gray-500">(<?= $job['reviews'] ?>)</span>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_favorite">
                                <input type="hidden" name="item_id" value="<?= $job['id'] ?>">
                                <input type="hidden" name="item_type" value="job">
                                <button type="submit" class="<?= isFavorite($job['id'], 'job') ? 'text-red-500' : 'text-gray-400 hover:text-red-500' ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <p class="text-gray-700 mb-4"><?= htmlspecialchars($job['description']) ?></p>
                    
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach (explode(',', $job['skills']) as $skill): ?>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            <?= htmlspecialchars(trim($skill)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-2xl font-bold text-green-600 mb-1">
                                <?= formatSalary($job['salary_min'], $job['salary_max']) ?>
                            </div>
                            <div class="text-sm text-gray-500"><?= htmlspecialchars($job['type']) ?></div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="apply">
                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                    応募する
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($page === 'freelancers'): ?>
            <!-- フリーランサー一覧ページ -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">優秀なフリーランサーを見つける</h2>
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" placeholder="スキルや国籍で検索..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        検索
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($demo_freelancers as $freelancer): ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-6 border border-gray-100">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-16 h-16 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                            <?= substr($freelancer['name'], 0, 1) ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($freelancer['name']) ?></h3>
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                    <?= htmlspecialchars($freelancer['nationality']) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-1 mb-2">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span class="text-sm font-medium"><?= $freelancer['rating'] ?></span>
                                <span class="text-xs text-gray-500">(<?= $freelancer['reviews'] ?>件)</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                経験: <?= $freelancer['experience'] ?>年 | <?= htmlspecialchars($freelancer['availability']) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold text-green-600">¥<?= number_format($freelancer['hourly_rate']) ?></div>
                            <div class="text-sm text-gray-500">時給</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-sm text-gray-600 mb-2">スキル:</div>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (explode(',', $freelancer['skills']) as $skill): ?>
                            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm">
                                <?= htmlspecialchars(trim($skill)) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-sm text-gray-600 mb-2">言語:</div>
                        <div class="flex gap-2">
                            <?php foreach (explode(',', $freelancer['languages']) as $lang): ?>
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-sm">
                                <?= htmlspecialchars(trim($lang)) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg font-medium transition-colors">
                            連絡する
                        </button>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_favorite">
                            <input type="hidden" name="item_id" value="<?= $freelancer['id'] ?>">
                            <input type="hidden" name="item_type" value="freelancer">
                            <button type="submit" class="px-4 py-2 rounded-lg transition-colors <?= isFavorite($freelancer['id'], 'freelancer') ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($page === 'messages'): ?>
            <!-- メッセージページ -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 h-96">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">メッセージ</h3>
                </div>
                <div class="flex-1 p-4 overflow-y-auto h-64">
                    <?php if (empty($_SESSION['messages'])): ?>
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-comments text-gray-300 text-5xl mb-4"></i>
                        <p>まだメッセージがありません</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($_SESSION['messages'] as $message): ?>
                        <div class="flex <?= $message['sender'] === 'user' ? 'justify-end' : 'justify-start' ?>">
                            <div class="max-w-xs px-4 py-2 rounded-lg <?= $message['sender'] === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' ?>">
                                <?php if ($message['sender'] === 'other'): ?>
                                <p class="text-xs font-medium mb-1 opacity-70"><?= htmlspecialchars($message['name']) ?></p>
                                <?php endif; ?>
                                <p><?= htmlspecialchars($message['text']) ?></p>
                                <p class="text-xs opacity-70 mt-1"><?= $message['time'] ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="p-4 border-t border-gray-200">
                    <input type="hidden" name="action" value="send_message">
                    <div class="flex gap-2">
                        <input name="message" type="text" placeholder="メッセージを入力..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($page === 'applications'): ?>
            <!-- 応募履歴ページ -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">応募履歴</h2>
                <?php if (empty($_SESSION['applications'])): ?>
                <div class="text-center py-12">
                    <i class="fas fa-briefcase text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">まだ応募した仕事がありません</p>
                    <a href="?page=jobs" class="mt-4 inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        仕事を探す
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($_SESSION['applications'] as $application): ?>
                    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($application['job']['title']) ?></h3>
                                <p class="text-gray-600"><?= htmlspecialchars($application['job']['company']) ?></p>
                                <p class="text-sm text-gray-500">応募日: <?= date('Y年m月d日 H:i', strtotime($application['applied_at'])) ?></p>
                            </div>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">
                                審査中
                            </span>
                        </div>
                        <p class="text-gray-700 mb-4"><?= htmlspecialchars($application['message']) ?></p>
                        <div class="flex gap-2">
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                進捗確認
                            </button>
                            <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition-colors">
                                詳細表示
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        <?php elseif ($page === 'favorites'): ?>
            <!-- お気に入りページ -->
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-6">お気に入り</h2>
                <?php if (empty($_SESSION['favorites'])): ?>
                <div class="text-center py-12">
                    <i class="fas fa-heart text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">まだお気に入りがありません</p>
                    <p class="text-gray-400 text-sm">気になる仕事や人材をハートマークで保存しましょう</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($_SESSION['favorites'] as $favorite_key => $favorite): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs mb-2 inline-block">
                                    <?= $favorite['type'] === 'job' ? '仕事' : 'フリーランサー' ?>
                                </span>
                                <h3 class="text-xl font-bold text-gray-900">
                                    <?= htmlspecialchars($favorite['type'] === 'job' ? $favorite['item']['title'] : $favorite['item']['name']) ?>
                                </h3>
                                <p class="text-gray-600">
                                    <?= htmlspecialchars($favorite['type'] === 'job' ? $favorite['item']['company'] : $favorite['item']['nationality']) ?>
                                </p>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_favorite">
                                <input type="hidden" name="item_id" value="<?= $favorite['item']['id'] ?>">
                                <input type="hidden" name="item_type" value="<?= $favorite['type'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-600">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </form>
                        </div>
                        <div class="flex gap-2">
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                詳細を見る
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </main>
<?php endif; ?>

<script>
// 基本的なJavaScript機能
document.addEventListener('DOMContentLoaded', function() {
    // 自動非表示メッセージ
    setTimeout(function() {
        const alerts = document.querySelectorAll('.bg-green-100');
        alerts.forEach(alert => {
            if (alert) alert.style.display = 'none';
        });
    }, 5000);
    
    // フォーム送信時の確認
    const applyForms = document.querySelectorAll('form[method="POST"]');
    applyForms.forEach(form => {
        const actionInput = form.querySelector('input[name="action"]');
        if (actionInput && actionInput.value === 'apply') {
            form.addEventListener('submit', function(e) {
                if (!confirm('この仕事に応募しますか？')) {
                    e.preventDefault();
                }
            });
        }
    });
});
</script>

</body>
</html>