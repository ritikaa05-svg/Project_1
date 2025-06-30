<?php
if(session_status() == PHP_SESSION_NONE) { session_start(); }
if(isset($_SESSION['admin'])){
	header('location: admin/index.php');
}else if(isset($_SESSION['manager'])){
	header('location: manager/index.php');
}else if(isset($_SESSION['employ'])){
	header('location: employ/index.php');
}
?>