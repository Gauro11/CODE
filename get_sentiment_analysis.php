<?php
/**
 * AJAX endpoint to return detailed sentiment analysis HTML
 */
require_once 'sentiment_analyzer.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
    $text = trim($_POST['text']);
    
    if (empty($text)) {
        echo '<div style="padding: 40px; text-align: center; color: #dc3545;">No text provided.</div>';
        exit;
    }
    
    $analyzer = new SentimentAnalyzer();
    echo $analyzer->getDetailedHtmlReport($text);
} else {
    echo '<div style="padding: 40px; text-align: center; color: #dc3545;">Invalid request.</div>';
}
?>