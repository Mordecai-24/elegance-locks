<?php
/** Dashboard layout, bottom part: closes <main>, footer and page wrapper. */
?>
    </main>

    <footer class="border-t border-line bg-white px-4 py-4 text-sm text-muted sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-1 sm:flex-row">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
            <p><?= e(APP_TAGLINE) ?></p>
        </div>
    </footer>
</div>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
