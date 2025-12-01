<?php
// stats.php
require_once 'config.php';
require_once 'functions.php';

session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

$accessToken = $_SESSION['access_token'];

// ユーザー情報の取得
$userProfile = callSpotifyApi('me', $accessToken);
if (isset($userProfile['error'])) {
    // トークン切れなどの場合、ログアウトしてトップへ
    session_destroy();
    header('Location: index.php');
    exit;
}

// 期間設定
$ranges = [
    'short_term' => '1ヶ月',
    'medium_term' => '半年',
    'long_term' => '全期間'
];

$data = [];

// 全期間のデータを取得
foreach ($ranges as $rangeKey => $rangeLabel) {
    // Top Tracks
    $tracks = callSpotifyApi("me/top/tracks?time_range={$rangeKey}&limit=50", $accessToken);
    $data[$rangeKey]['tracks'] = $tracks['items'] ?? [];

    // Top Artists
    $artists = callSpotifyApi("me/top/artists?time_range={$rangeKey}&limit=50", $accessToken);
    $data[$rangeKey]['artists'] = $artists['items'] ?? [];

    // 合計再生時間の計算
    $totalDurationMs = 0;
    foreach ($data[$rangeKey]['tracks'] as $track) {
        $totalDurationMs += $track['duration_ms'];
    }
    $data[$rangeKey]['total_duration'] = formatTotalDuration($totalDurationMs);
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Spotify Stats</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function switchTab(range) {
            // すべてのコンテンツを非表示
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // 選択されたコンテンツを表示
            document.getElementById('content-' + range).classList.remove('hidden');

            // タブのスタイル更新
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('text-green-500', 'border-green-500');
                el.classList.add('text-gray-400', 'border-transparent');
            });
            document.getElementById('btn-' + range).classList.add('text-green-500', 'border-green-500');
            document.getElementById('btn-' + range).classList.remove('text-gray-400', 'border-transparent');
        }
    </script>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="min-h-screen pb-12">
    <!-- Header -->
    <header class="bg-neutral-900 border-b border-neutral-800 sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <?php if (!empty($userProfile['images'])): ?>
                    <img src="<?php echo $userProfile['images'][0]['url']; ?>" alt="Profile"
                        class="w-10 h-10 rounded-full border-2 border-green-500">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center">
                        <span class="text-xl font-bold"><?php echo substr($userProfile['display_name'], 0, 1); ?></span>
                    </div>
                <?php endif; ?>
                <h1 class="text-xl font-bold"><?php echo htmlspecialchars($userProfile['display_name']); ?>'s Stats</h1>
            </div>
            <a href="logout.php" class="text-sm text-gray-400 hover:text-white transition">ログアウト</a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Tabs -->
        <div class="flex justify-center space-x-8 mb-8 border-b border-neutral-800">
            <?php foreach ($ranges as $key => $label): ?>
                <button id="btn-<?php echo $key; ?>" onclick="switchTab('<?php echo $key; ?>')"
                    class="tab-btn pb-4 px-2 text-lg font-medium border-b-2 transition duration-300 <?php echo $key === 'short_term' ? 'text-green-500 border-green-500' : 'text-gray-400 border-transparent hover:text-white'; ?>">
                    <?php echo $label; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Content Areas -->
        <?php foreach ($ranges as $key => $label): ?>
            <div id="content-<?php echo $key; ?>" class="tab-content <?php echo $key !== 'short_term' ? 'hidden' : ''; ?>">

                <!-- Summary Stats -->
                <div
                    class="mb-8 p-6 bg-gradient-to-r from-green-900/20 to-neutral-900 rounded-xl border border-green-900/30">
                    <h2 class="text-green-400 text-sm font-bold uppercase tracking-wider mb-1">Total Playtime (Top 50)</h2>
                    <p class="text-3xl font-bold text-white"><?php echo $data[$key]['total_duration']; ?></p>
                    <p class="text-xs text-gray-500 mt-2">※ この期間のトップ50曲の合計再生時間</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Top Tracks -->
                    <section>
                        <h2 class="text-2xl font-bold mb-6 flex items-center">
                            <span class="bg-green-500 w-1 h-8 mr-3 rounded-full"></span>
                            Top Tracks
                        </h2>
                        <div class="space-y-4">
                            <?php foreach ($data[$key]['tracks'] as $index => $track): ?>
                                <div class="flex items-center p-3 hover:bg-neutral-800 rounded-lg transition group">
                                    <div class="w-8 text-center text-gray-500 font-bold mr-4 group-hover:text-green-500">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <img src="<?php echo $track['album']['images'][2]['url'] ?? ''; ?>" alt="Art"
                                        class="w-12 h-12 rounded shadow-lg mr-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-white font-medium truncate">
                                            <?php echo htmlspecialchars($track['name']); ?>
                                        </div>
                                        <div class="text-gray-400 text-sm truncate">
                                            <?php echo htmlspecialchars(implode(', ', array_column($track['artists'], 'name'))); ?>
                                        </div>
                                    </div>
                                    <div class="text-gray-500 text-sm ml-4">
                                        <?php echo formatDuration($track['duration_ms']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Top Artists -->
                    <section>
                        <h2 class="text-2xl font-bold mb-6 flex items-center">
                            <span class="bg-blue-500 w-1 h-8 mr-3 rounded-full"></span>
                            Top Artists
                        </h2>
                        <div class="space-y-4">
                            <?php foreach ($data[$key]['artists'] as $index => $artist): ?>
                                <div class="flex items-center p-3 hover:bg-neutral-800 rounded-lg transition group">
                                    <div class="w-8 text-center text-gray-500 font-bold mr-4 group-hover:text-blue-500">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <img src="<?php echo $artist['images'][2]['url'] ?? ''; ?>" alt="Art"
                                        class="w-12 h-12 rounded-full shadow-lg mr-4 object-cover">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-white font-medium truncate">
                                            <?php echo htmlspecialchars($artist['name']); ?>
                                        </div>
                                        <div class="text-gray-400 text-sm capitalize">
                                            <?php echo htmlspecialchars($artist['genres'][0] ?? 'Artist'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>
        <?php endforeach; ?>
    </main>
</body>

</html>