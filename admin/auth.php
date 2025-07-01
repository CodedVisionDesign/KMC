<?php
session_start();

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
} 