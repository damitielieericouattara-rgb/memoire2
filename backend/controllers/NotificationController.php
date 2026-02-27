<?php
// Fichier: /backend/controllers/NotificationController.php

class NotificationController extends Controller {
    public function index() {
        $user = $this->getUser();
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY sent_at DESC LIMIT 50");
            $stmt->execute([$user['id']]);
            $notifications = $stmt->fetchAll();
            Response::success($notifications, 'Notifications récupérées');
        } catch (Exception $e) {
            Response::success([], 'Notifications récupérées');
        }
    }

    public function markAsRead($id) {
        $user = $this->getUser();
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
            Response::success(null, 'Notification marquée comme lue');
        } catch (Exception $e) {
            Response::success(null, 'OK');
        }
    }

    public function markAllAsRead() {
        $user = $this->getUser();
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            Response::success(null, 'Toutes les notifications marquées comme lues');
        } catch (Exception $e) {
            Response::success(null, 'OK');
        }
    }

    public function poll() {
        $user = $this->getUser();
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY sent_at DESC LIMIT 10");
            $stmt->execute([$user['id']]);
            $notifications = $stmt->fetchAll();
            Response::success($notifications, 'Nouvelles notifications');
        } catch (Exception $e) {
            Response::success([], 'Aucune notification');
        }
    }
}
