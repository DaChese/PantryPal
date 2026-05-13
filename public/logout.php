<?php
/*
 * Purpose: Log the user out and redirect to login.
 */

require_once dirname(__DIR__) . '/src/auth.php';
require_once dirname(__DIR__) . '/src/helpers.php';

ensure_session_started();
logout_user();
set_flash_message('success', 'You have been logged out.');
redirect('login.php');
