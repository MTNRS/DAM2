<?php
require_once __DIR__.'/inc/core.php';session_start_safe();$_SESSION=[];session_regenerate_id(true);session_destroy();header('Location: login.php');
