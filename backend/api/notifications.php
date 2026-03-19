<?php
/**
 * SneakX — API Notifications
 * /backend/api/notifications.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? 'liste';
$input   = getInput();
$userId  = $_SESSION['user_id'];
$db      = Database::getInstance();

switch ($action) {

    case 'liste':
        $stmt = $db->prepare(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30"
        );
        $stmt->execute([$userId]);
        $notifs = $stmt->fetchAll();

        $unread = array_filter($notifs, fn($n) => !$n['is_read']);

        jsonSuccess(['notifications' => $notifs, 'unread' => count($unread)]);
        break;

    case 'read':
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        } else {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
        }
        jsonSuccess([], 'Notification(s) marquée(s) comme lue(s).');
        break;

    case 'count':
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        jsonSuccess(['count' => (int) $stmt->fetchColumn()]);
        break;

    default:
        jsonError('Action inconnue.', 404);
}
