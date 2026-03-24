<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/settings_helper.php';

$settings = getSiteSettings($conn);
?>
<header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-blue-700">
            <?php echo htmlspecialchars($settings['site_name']); ?>
        </h1>
        <nav class="flex items-center gap-4">
            <a href="index.php" class="text-slate-700 hover:text-blue-700">Home</a>
            <a href="login.php" class="text-slate-700 hover:text-blue-700">Login</a>
        </nav>
    </div>
</header>