<?php
$key = 'AQ.Ab8RN6JhAJ6UpK4cj1hBZ7uVTE7OZvzdjQdo5gMkTr5wF5XAPQ';
$url = 'https://generativelanguage.googleapis.com/v1/models?key=' . $key;
$response = file_get_contents($url);
echo $response;
?>