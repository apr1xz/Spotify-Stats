<?php
// config.php

// Spotify Developer Dashboardで取得した値を設定してください
define('SPOTIFY_CLIENT_ID', 'df57cab0006e4c63bb787f754ff458b3');
define('SPOTIFY_CLIENT_SECRET', '9c5b1c58889a451aab19f6e074f20380');
define('SPOTIFY_REDIRECT_URI', 'https://127.0.0.1/Spotify-Stats/callback.php');

// 必要なスコープ
define('SPOTIFY_SCOPES', 'user-top-read user-read-recently-played');
?>