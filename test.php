<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$photo = \App\Models\DailyCleanPhoto::first();
echo \Illuminate\Support\Facades\Storage::url($photo->filename);
