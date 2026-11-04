<?php
audit('logout');
$_SESSION = [];
session_destroy();
redirect('auth/login');
