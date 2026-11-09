<?php
/**
 * Dashboard layout, top part: <head>, sidebar, header, opening of <main>.
 * The page sets these before including it:
 *   $user, $pageTitle, $activePage, and optionally $pageSubtitle.
 */
require_once __DIR__ . '/navigation.php';

$documentTitle = $pageTitle;
$navItems = nav_items($user['role']);
[$notifyCount, $notifyText, $notifyLink] = notification_info($user);
$roleLabels = ['admin' => 'Administrator', 'salonist' => 'Salonist', 'customer' => 'Customer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/head.php'; ?>
</head>
<body class="min-h-screen bg-surface font-sans text-ink antialiased">

<div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 z-30 bg-black/40 lg:hidden" hidden></div>

<aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-white" aria-label="Sidebar">
    <div class="flex h-16 items-center gap-3 border-b border-line px-5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary font-serif text-sm font-semibold text-white">EL</span>
        <span class="sidebar-label font-serif text-lg font-semibold text-secondary"><?= e(APP_NAME) ?></span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $item['key'] === $activePage; ?>
            <a href="<?= e(url($item['path'])) ?>"
               title="<?= e($item['label']) ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>
               class="nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors
                      <?= $isActive ? 'bg-secondary text-white' : 'text-ink hover:bg-surface hover:text-secondary' ?>">
                <?= icon($item['icon'], 'h-5 w-5 shrink-0') ?>
                <span class="sidebar-label"><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="<?= e(url('logout.php')) ?>" class="border-t border-line p-3">
        <?= csrf_field() ?>
        <button type="submit" title="Log out"
                class="nav-link flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-muted transition-colors hover:bg-surface hover:text-secondary">
            <?= icon('logout', 'h-5 w-5 shrink-0') ?>
            <span class="sidebar-label">Log out</span>
        </button>
    </form>
</aside>

<div class="main-wrap flex min-h-screen flex-col lg:pl-64">

    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" id="sidebar-open" class="rounded-lg p-2 text-muted hover:bg-surface hover:text-secondary lg:hidden" aria-label="Open menu">
                <?= icon('menu', 'h-6 w-6') ?>
            </button>
            <button type="button" id="sidebar-collapse" class="hidden rounded-lg p-2 text-muted hover:bg-surface hover:text-secondary lg:inline-flex" aria-label="Collapse or expand sidebar" title="Collapse or expand sidebar">
                <?= icon('menu', 'h-5 w-5') ?>
            </button>
            <p class="font-serif text-lg font-semibold"><?= e($pageTitle) ?></p>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
            <a href="<?= e(url($notifyLink)) ?>" class="relative rounded-lg p-2 text-muted hover:bg-surface hover:text-secondary" title="<?= e($notifyText) ?>" aria-label="<?= e($notifyText) ?>">
                <?= icon('bell', 'h-6 w-6') ?>
                <?php if ($notifyCount > 0): ?>
                    <span class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-secondary px-1 text-[10px] font-semibold text-white"><?= $notifyCount > 99 ? '99+' : $notifyCount ?></span>
                <?php endif; ?>
            </a>
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white"><?= e(initials($user['full_name'])) ?></span>
                <div class="hidden leading-tight sm:block">
                    <p class="text-sm font-medium"><?= e($user['full_name']) ?></p>
                    <p class="text-xs text-muted"><?= e($roleLabels[$user['role']] ?? '') ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="font-serif text-2xl font-semibold sm:text-3xl"><?= e($pageTitle) ?></h1>
            <?php if (!empty($pageSubtitle)): ?>
                <p class="mt-1 text-sm text-muted"><?= e($pageSubtitle) ?></p>
            <?php endif; ?>
        </div>
        <?= flash_render() ?>
