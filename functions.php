<?php
// functions.php

require_once 'config.php';

function getAccessToken($code)
{
    $url = 'https://accounts.spotify.com/api/token';
    $data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => SPOTIFY_REDIRECT_URI,
        'client_id' => SPOTIFY_CLIENT_ID,
        'client_secret' => SPOTIFY_CLIENT_SECRET,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Error: ' . curl_error($ch));
    }

    curl_close($ch);

    return json_decode($response, true);
}

function callSpotifyApi($endpoint, $accessToken)
{
    $url = 'https://api.spotify.com/v1/' . $endpoint;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ['error' => curl_error($ch)];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        // Token expired or invalid
        return ['error' => 'Unauthorized'];
    }

    return json_decode($response, true);
}

function formatDuration($ms)
{
    $seconds = floor($ms / 1000);
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;

    return sprintf('%d:%02d', $minutes, $remainingSeconds);
}

function formatTotalDuration($ms)
{
    $seconds = floor($ms / 1000);
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    if ($hours > 0) {
        return sprintf('%d時間 %d分', $hours, $minutes);
    }
    return sprintf('%d分', $minutes);
}
?>