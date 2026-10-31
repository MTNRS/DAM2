<?php
require_once __DIR__.'/../api/auth_common.php';
if(!JocarsaDB::installed()){header('Location: instalar.php');exit;}
requireAdmin(false);header('Location: ../modelo.php');exit;
