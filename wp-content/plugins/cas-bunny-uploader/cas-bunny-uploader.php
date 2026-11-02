<?php
/**
 * Plugin Name: Crystal Lesson Video Uploads
 * Description: Owned lesson uploads directly to Bunny Stream, with server-only credentials.
 * Version: 1.1.1
 * Requires PHP: 8.0
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/includes.php';
CAS_Bunny_Uploader::boot();
