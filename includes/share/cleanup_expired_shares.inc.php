<?php
// Script to be run by cron job to clean up expired shares
require_once __DIR__ . '/includes/config/config_session.inc.php';
require_once __DIR__ . '/includes/config/dbh.inc.php';
require_once __DIR__ . '/includes/share/share_model.inc.php';

// Mark all expired shares as inactive
$expiredCount = mark_all_expired_shares($pdo);

echo "Expired shares cleanup completed. Marked {$expiredCount} shares as expired.";