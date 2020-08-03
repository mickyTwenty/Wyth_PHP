<?php
error_reporting(0);
ini_set('display_errors', 'Off');

header( 'Location: ' . substr($_SERVER['PHP_SELF'], 0, -strlen(basename($_SERVER['PHP_SELF']))) . 'dashboard' );