<?php
/**
 * Split-screen layout for login, registration and OTP pages (top part).
 * Pages set: $documentTitle, $authHeading, $authSubtitle.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">
<div class="flex min-h-screen">

    <!-- Left: photo and brand message (desktop only) -->
    <section class="relative hidden w-1/2 bg-secondary lg:block">
        <img src="<?= e(AUTH_IMAGE_URL) ?>" alt="Stylist styling a client's hair in a salon" class="absolute inset-0 h-full w-full object-cover" loading="eager">
        <div class="absolute inset-0 bg-ink/50"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <a href="<?= e(url('index.php')) ?>" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white font-serif text-sm font-semibold text-secondary">EL</span>
                <span class="font-serif text-2xl font-semibold"><?= e(APP_NAME) ?></span>
            </a>
            <div>
                <p class="font-serif text-4xl font-semibold leading-tight xl:text-5xl"><?= e(APP_TAGLINE) ?></p>
                <p class="mt-4 max-w-md text-white/85">Book your next appointment, follow its progress and let our stylists take care of the rest.</p>
            </div>
        </div>
    </section>

    <!-- Right: form -->
    <section class="flex w-full flex-col lg:w-1/2">
        <div class="flex items-center gap-3 bg-secondary px-6 py-4 text-white lg:hidden">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white font-serif text-sm font-semibold text-secondary">EL</span>
            <div class="leading-tight">
                <p class="font-serif text-lg font-semibold"><?= e(APP_NAME) ?></p>
                <p class="text-xs text-white/85"><?= e(APP_TAGLINE) ?></p>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-center px-6 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <h1 class="font-serif text-3xl font-semibold"><?= e($authHeading) ?></h1>
                <?php if (!empty($authSubtitle)): ?>
                    <p class="mt-2 text-sm text-muted"><?= e($authSubtitle) ?></p>
                <?php endif; ?>
                <div class="mt-8">
                    <?= flash_render() ?>
