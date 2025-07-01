<?php
// Include the modular header
if (file_exists(__DIR__ . '/header.php')) {
    include __DIR__ . '/header.php';
} else {
    error_log('Template header.php not found');
    die('Template header not found');
}
?>

<?php echo isset($content) ? $content : ''; ?>

<?php
// Include the modular footer  
if (file_exists(__DIR__ . '/footer.php')) {
    include __DIR__ . '/footer.php';
} else {
    error_log('Template footer.php not found');
    die('Template footer not found');
}
?> 