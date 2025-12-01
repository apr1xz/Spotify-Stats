<?php
// index.php
require_once 'config.php';

session_start();

// すでにログイン済みなら stats.php へリダイレクト
if (isset($_SESSION['access_token'])) {
    header('Location: stats.php');
    exit;
}

$loginUrl = 'https://accounts.spotify.com/authorize?' . http_build_query([
    'client_id' => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
    'scope' => SPOTIFY_SCOPES,
]);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Stats Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen">
    <div
        class="text-center space-y-8 p-8 bg-neutral-900 rounded-xl shadow-2xl max-w-md w-full mx-4 border border-neutral-800">
        <div class="space-y-2">
            <h1 class="text-4xl font-bold text-green-500 tracking-tighter">Spotify Stats</h1>
            <p class="text-gray-400">あなたのリスニング傾向を分析します</p>
        </div>

        <div class="py-8">
            <svg class="w-24 h-24 mx-auto text-green-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z" />
            </svg>
        </div>

        <a href="<?php echo htmlspecialchars($loginUrl); ?>"
            class="inline-block w-full bg-green-500 hover:bg-green-400 text-black font-bold py-4 px-8 rounded-full transition duration-300 transform hover:scale-105 shadow-lg text-lg">
            Spotifyでログイン
        </a>

        <p class="text-xs text-gray-500 mt-4">
            ※ このアプリはSpotifyの公式アプリではありません。<br>
            認証はSpotifyのサーバーで行われます。
        </p>

        <div class="border-t border-neutral-800 pt-8 mt-8">
            <h2 class="text-2xl font-bold text-white mb-4">またはデータファイルをアップロード</h2>
            <p class="text-gray-400 text-sm mb-6">
                Spotifyからダウンロードした <code>StreamingHistory.json</code> ファイルを解析します。<br>
                <a href="https://www.spotify.com/account/privacy/" target="_blank"
                    class="text-green-500 hover:underline">データのダウンロードはこちら</a>
            </p>

            <form action="analyze.php" method="post" enctype="multipart/form-data" class="space-y-4">
                <div class="relative group">
                    <input type="file" name="json_files[]" id="json_files" multiple accept=".json" required class="block w-full text-sm text-gray-400
                        file:mr-4 file:py-3 file:px-6
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-neutral-800 file:text-green-500
                        hover:file:bg-neutral-700
                        cursor-pointer focus:outline-none" />
                </div>
                <button type="submit"
                    class="w-full bg-neutral-800 hover:bg-neutral-700 text-white font-bold py-3 px-6 rounded-full transition duration-300 border border-neutral-700 hover:border-green-500">
                    解析する
                </button>
            </form>
        </div>
    </div>
</body>

</html>