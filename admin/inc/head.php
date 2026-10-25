<?php
/** Shared <head> + opening <body> for admin pages. Expects $title. */
$pageTitle = isset($title) ? $title : 'Gallery admin';
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pageTitle) ?> — CCFM Gallery Admin</title>
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/assets/css/styles.css?v=4">
  <link rel="stylesheet" href="/admin/assets/admin.css?v=1">
  <script src="/assets/js/main.js?v=4" defer></script>
  <script src="/admin/assets/admin.js?v=1" defer></script>
</head>
<body class="admin-body">
