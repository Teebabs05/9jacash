<?php
/**
 * Bulk email delivery cron job.
 *
 * Sends a bounded batch of pending bulk_email_recipients per run so a
 * campaign to thousands of users never times out a web request - the
 * admin queues the campaign instantly on admin/send-email.php and this
 * script drains the queue gradually. Safe to run frequently (every
 * few minutes is a good default); it only touches rows still pending.
 *
 */

// cPanel cron example (every 2 minutes) - kept as a line comment, not in
// the docblock above, because the interval syntax below contains "*/"
// which would otherwise close that comment block early:
//   */2 * * * * /usr/bin/php /home/USERNAME/public_html/cron/send-bulk-emails.php >> /home/USERNAME/public_html/logs/cron.log 2>&1

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/config.php';

const BULK_EMAIL_BATCH_SIZE = 50;

$pdo = db();

$stmt = $pdo->prepare(
    "SELECT ber.id AS recipient_id, ber.bulk_email_id, u.email, u.full_name, be.subject, be.body
     FROM bulk_email_recipients ber
     INNER JOIN bulk_emails be ON be.id = ber.bulk_email_id
     INNER JOIN users u ON u.id = ber.user_id
     WHERE ber.status = 'pending'
     ORDER BY ber.id ASC
     LIMIT " . BULK_EMAIL_BATCH_SIZE
);
$stmt->execute();
$batch = $stmt->fetchAll();

$sent = 0;
$failed = 0;
$touchedCampaigns = [];

foreach ($batch as $row) {
    // Body is admin-authored (same trust level as every other admin
    // action on this platform), so it's sent as-is rather than escaped -
    // that's what lets an admin use basic HTML formatting, as the
    // compose form on admin/send-email.php says is supported.
    $ok = Mailer::send((string) $row['email'], (string) $row['full_name'], (string) $row['subject'], nl2br((string) $row['body']));

    $pdo->prepare('UPDATE bulk_email_recipients SET status = ?, sent_at = NOW() WHERE id = ?')
        ->execute([$ok ? 'sent' : 'failed', $row['recipient_id']]);

    $ok ? $sent++ : $failed++;
    $touchedCampaigns[(int) $row['bulk_email_id']] = true;
}

foreach (array_keys($touchedCampaigns) as $bulkEmailId) {
    $pdo->prepare(
        "UPDATE bulk_emails SET
            sent_count = (SELECT COUNT(*) FROM bulk_email_recipients WHERE bulk_email_id = ? AND status = 'sent'),
            failed_count = (SELECT COUNT(*) FROM bulk_email_recipients WHERE bulk_email_id = ? AND status = 'failed'),
            status = IF((SELECT COUNT(*) FROM bulk_email_recipients WHERE bulk_email_id = ? AND status = 'pending') = 0, 'completed', 'processing'),
            completed_at = IF((SELECT COUNT(*) FROM bulk_email_recipients WHERE bulk_email_id = ? AND status = 'pending') = 0, NOW(), completed_at)
         WHERE id = ?"
    )->execute([$bulkEmailId, $bulkEmailId, $bulkEmailId, $bulkEmailId, $bulkEmailId]);
}

$line = sprintf('[%s] Bulk email run: %d sent, %d failed.', date('Y-m-d H:i:s'), $sent, $failed);
echo $line . PHP_EOL;
app_log('info', $line);
