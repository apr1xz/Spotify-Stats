<?php
// analyze.php

// エラー表示設定（デバッグ用、本番ではオフにすることを推奨）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// データ保持用配列
$monthlyStats = [];
$trackStats = [];
$artistStats = [];
$totalPlayTimeMs = 0;

// ファイルがアップロードされたか確認
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_files'])) {
    $files = $_FILES['json_files'];
    $fileCount = count($files['name']);

    for ($i = 0; $i < $fileCount; $i++) {
        // エラーチェック
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpName = $files['tmp_name'][$i];
        $content = file_get_contents($tmpName);
        $json = json_decode($content, true);

        if (!is_array($json)) {
            continue;
        }

        foreach ($json as $record) {
            // 必要なキーがあるか確認
            if (!isset($record['endTime'], $record['artistName'], $record['trackName'], $record['msPlayed'])) {
                continue;
            }

            $msPlayed = $record['msPlayed'];
            $artist = $record['artistName'];
            $track = $record['trackName'];
            $endTime = $record['endTime']; // "2023-11-26 15:30"

            // 総再生時間
            $totalPlayTimeMs += $msPlayed;

            // 月別集計 (Y-m)
            $month = substr($endTime, 0, 7);
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = 0;
            }
            $monthlyStats[$month] += $msPlayed;

            // アーティスト別集計
            if (!isset($artistStats[$artist])) {
                $artistStats[$artist] = 0;
            }
            $artistStats[$artist] += $msPlayed;

            // トラック別集計
            // 同じ曲名でもアーティストが違う場合を考慮してキーを生成
            $trackKey = $track . ' - ' . $artist;
            if (!isset($trackStats[$trackKey])) {
                $trackStats[$trackKey] = [
                    'name' => $track,
                    'artist' => $artist,
                    'ms' => 0
                ];
            }
            $trackStats[$trackKey]['ms'] += $msPlayed;
        }
    }

    // ソート (降順)
    arsort($artistStats);

    // トラックは ms でソート
    usort($trackStats, function ($a, $b) {
        return $b['ms'] <=> $a['ms'];
    });

    // 月別は日付順にソート
    ksort($monthlyStats);
}

// ミリ秒をフォーマットする関数
function formatDuration($ms)
{
    $seconds = floor($ms / 1000);
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);

    if ($hours > 0) {
        return $hours . '時間 ' . ($minutes % 60) . '分';
    }
    return $minutes . '分 ' . ($seconds % 60) . '秒';
}

// ミリ秒を時間に変換（グラフ用）
function msToHours($ms)
{
    return round($ms / (1000 * 60 * 60), 1);
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify History Analysis</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .card {
            background-color: #181818;
            border: 1px solid #282828;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            /* Ensure children don't overflow rounded corners */
        }

        .card-header {
            background-color: #282828;
            border-bottom: 1px solid #333;
            font-weight: bold;
            color: #ffffff;
            font-size: 1.1rem;
        }

        /* Table Styles - Dark Mode */
        .table {
            color: #e0e0e0;
            margin-bottom: 0;
            background-color: #181818;
            --bs-table-bg: #181818;
            --bs-table-color: #e0e0e0;
            --bs-table-hover-bg: #282828;
            --bs-table-hover-color: #ffffff;
        }

        .table thead th {
            border-bottom: 2px solid #333;
            color: #ffffff;
            background-color: #282828;
            font-weight: 700;
        }

        .table td,
        .table th {
            border-top: 1px solid #333;
            vertical-align: middle;
            padding: 12px 15px;
            border-color: #333;
        }

        .table-hover tbody tr:hover {
            color: #ffffff;
            background-color: #333;
        }

        h1,
        h2,
        h3 {
            font-weight: 700;
            color: #ffffff;
        }

        .text-spotify {
            color: #1db954;
        }

        .bg-spotify {
            background-color: #1db954 !important;
            color: #000;
        }

        .btn-spotify {
            background-color: #1db954;
            color: #000;
            border-radius: 500px;
            font-weight: bold;
            border: none;
            padding: 10px 24px;
            transition: all 0.2s;
        }

        .btn-spotify:hover {
            background-color: #1ed760;
            color: #000;
            transform: scale(1.02);
        }

        /* Chart container */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        /* Custom scrollbar for tables if needed */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #121212;
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #888;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-black sticky-top border-bottom border-secondary"
        style="border-color: #333 !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-spotify">Spotify</span> Stats Analysis
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">戻る</a>
        </div>
    </nav>

    <div class="container py-5">
        <?php if (empty($monthlyStats)): ?>
            <div class="text-center py-5">
                <h2 class="mb-4">データが見つかりませんでした</h2>
                <p class="text-muted mb-4">StreamingHistory.json ファイルを正しくアップロードしてください。</p>
                <a href="index.php" class="btn btn-spotify px-4 py-2">アップロード画面に戻る</a>
            </div>
        <?php else: ?>

            <!-- Summary -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card p-4 text-center bg-gradient border-0"
                        style="background: linear-gradient(135deg, #1db954 0%, #104922 100%);">
                        <h3 class="text-white mb-2">総再生時間</h3>
                        <div class="display-3 fw-bold text-white mb-2"><?php echo formatDuration($totalPlayTimeMs); ?></div>
                        <p class="text-white-50 mb-0 fs-5">Total Playtime</p>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header py-3">
                            <i class="bi bi-bar-chart-fill me-2"></i>月別再生時間 (時間)
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Top Tracks -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <span>よく聴いた曲 (Top 50)</span>
                            <span class="badge bg-spotify rounded-pill">Tracks</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px;">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th style="width: 60px;" class="text-center">#</th>
                                            <th>曲名 / アーティスト</th>
                                            <th class="text-end">再生時間</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 0;
                                        foreach ($trackStats as $track):
                                            if ($count >= 50)
                                                break;
                                            $count++;
                                            ?>
                                            <tr>
                                                <td class="text-center text-secondary fw-bold"><?php echo $count; ?></td>
                                                <td>
                                                    <div class="fw-bold text-light mb-1">
                                                        <?php echo htmlspecialchars($track['name']); ?>
                                                    </div>
                                                    <div class="small text-white-50">
                                                        <?php echo htmlspecialchars($track['artist']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-end font-monospace small text-light">
                                                    <?php echo formatDuration($track['ms']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Artists -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <span>トップアーティスト (Top 50)</span>
                            <span class="badge bg-spotify rounded-pill">Artists</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 600px;">
                                <table class="table table-hover mb-0">
                                    <thead class="sticky-top">
                                        <tr>
                                            <th style="width: 60px;" class="text-center">#</th>
                                            <th>アーティスト名</th>
                                            <th class="text-end">再生時間</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 0;
                                        foreach ($artistStats as $artist => $ms):
                                            if ($count >= 50)
                                                break;
                                            $count++;
                                            ?>
                                            <tr>
                                                <td class="text-center text-secondary fw-bold"><?php echo $count; ?></td>
                                                <td>
                                                    <div class="fw-bold text-light"><?php echo htmlspecialchars($artist); ?>
                                                    </div>
                                                </td>
                                                <td class="text-end font-monospace small text-light">
                                                    <?php echo formatDuration($ms); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($monthlyStats)): ?>
        <script>
            const ctx = document.getElementById('monthlyChart').getContext('2d');

            const labels = <?php echo json_encode(array_keys($monthlyStats)); ?>;
            const dataMs = <?php echo json_encode(array_values($monthlyStats)); ?>;
            const dataHours = dataMs.map(ms => (ms / (1000 * 60 * 60)).toFixed(1));

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '再生時間 (時間)',
                        data: dataHours,
                        backgroundColor: 'rgba(29, 185, 84, 0.8)',
                        borderColor: '#1db954',
                        borderWidth: 1,
                        borderRadius: 4,
                        hoverBackgroundColor: '#1ed760'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#ccc',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#333'
                            },
                            ticks: {
                                color: '#e0e0e0',
                                font: {
                                    family: "'Helvetica Neue', Helvetica, Arial, sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#e0e0e0',
                                font: {
                                    family: "'Helvetica Neue', Helvetica, Arial, sans-serif"
                                }
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>