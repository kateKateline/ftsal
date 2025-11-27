<?php
// Simple auth helpers

function is_logged_in() {
    return isset($_SESSION) && isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user() {
    return is_logged_in() ? $_SESSION['user'] : null;
}

function current_user_id() {
    $u = current_user();
    return $u ? intval($u['id']) : null;
}

function login_user_from_row($row) {
    // $row is associative array from users table
    $_SESSION['user'] = $row;
}

function logout_user() {
    if (isset($_SESSION['user'])) unset($_SESSION['user']);
}
