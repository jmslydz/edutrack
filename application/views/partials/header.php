<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($title) ? html_escape($title) : 'EduTrack'; ?></title>
<link rel="stylesheet" href="<?php echo base_url('assets/css/design-system.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/dashboard.css'); ?>">
</head>
<body>
<div class="app-shell">