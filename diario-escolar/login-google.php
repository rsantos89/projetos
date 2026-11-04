<?php
// URI de redirecionamento autorizada no Google Cloud: https://SEU-DOMINIO/caminho/login-google.php
$_GET['r'] = 'auth/google';
require __DIR__ . '/index.php';
