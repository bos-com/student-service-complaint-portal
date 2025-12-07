<?php
session_start();
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Variables:\n";
print_r($_SESSION);
echo "Cookies:\n";
print_r($_COOKIE);
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "</pre>";
?>