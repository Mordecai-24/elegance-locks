<?php
require_once __DIR__ . '/../includes/init.php';

$user = require_role('admin');
$pdo  = db();

$errors = [];

// ---- Activate / deactivate a service ----
if (is_post() && post('action') === 'toggle') {
    csrf_verify();
    $pdo->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = ?')->execute([(int) ($_POST['service_id'] ?? 0)]);
    flash('success', 'Service updated.');
    redirect('admin/services.php');
}

// ---- Add or edit a service ----
$editId  = (int) ($_GET['edit'] ?? 0);
$editing = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    $editing = $stmt->fetch() ?: null;
}

if (is_post() && post('action') === 'save') {
    csrf_verify();

    $id          = (int) ($_POST['service_id'] ?? 0);
    $name        = preg_replace('/\s+/', ' ', post('name'));
    $description = mb_substr(post('description'), 0, 255);
    $price       = post('price');
    $duration    = post('duration_minutes');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'Please enter a service name.';
    }
    if (!is_numeric($price) || (float) $price < 0 || (float) $price > 1000000) {
        $errors[] = 'Please enter a valid price.';
    }
    if (!ctype_digit($duration) || (int) $duration < SLOT_MINUTES || (int) $duration > 480) {
        $errors[] = 'Duration must be between ' . SLOT_MINUTES . ' and 480 minutes.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM services WHERE name = ? AND id <> ?');
        $stmt->execute([$name, $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'A service with this name already exists.';
        }
    }

    if (!$errors) {
        $desc = $description !== '' ? $description : null;
        if ($id > 0) {
            $pdo->prepare('UPDATE services SET name = ?, description = ?, price = ?, duration_minutes = ? WHERE id = ?')
                ->execute([$name, $desc, $price, (int) $duration, $id]);
            flash('success', 'Service updated.');
        } else {
            $pdo->prepare('INSERT INTO services (name, description, price, duration_minutes) VALUES (?, ?, ?, ?)')
                ->execute([$name, $desc, $price, (int) $duration]);
            flash('success', 'Service added.');
        }
        redirect('admin/services.php');
    }
}

$services = $pdo->query('SELECT * FROM services ORDER BY is_active DESC, name')->fetchAll();

// Form values: submitted first, then the service being edited, then blanks.
$val = function (string $key, string $default = '') use ($editing) {
    return is_post() ? old($key) : e((string) ($editing[$key] ?? $default));
};

$pageTitle    = 'Services';
$pageSubtitle = 'The treatments customers can book.';
$activePage   = 'services';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<?= errors_render($errors) ?>

<div class="grid gap-10 xl:grid-cols-3">

    <section class="xl:col-span-2">
        <div class="table-wrap rounded-xl border border-line bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3">Service</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <?php foreach ($services as $s): ?>
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-medium"><?= e($s['name']) ?></p>
                                <?php if ($s['description']): ?><p class="max-w-sm text-xs text-muted"><?= e($s['description']) ?></p><?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><?= e(format_price($s['price'])) ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= (int) $s['duration_minutes'] ?> min</td>
                            <td class="px-4 py-3"><?= (int) $s['is_active'] === 1 ? '<span class="text-emerald-700">Active</span>' : '<span class="text-muted">Hidden</span>' ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="<?= e(url('admin/services.php?edit=' . (int) $s['id'])) ?>" class="font-medium text-secondary hover:underline">Edit</a>
                                    <form method="post" action="<?= e(url('admin/services.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" class="font-medium text-muted hover:text-secondary hover:underline"><?= (int) $s['is_active'] === 1 ? 'Hide' : 'Show' ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="font-serif text-xl font-semibold"><?= $editing ? 'Edit service' : 'Add a service' ?></h2>
        <form method="post" action="<?= e(url('admin/services.php' . ($editing ? '?edit=' . (int) $editing['id'] : ''))) ?>" class="mt-4 max-w-md space-y-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="service_id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Name</label>
                <input type="text" id="name" name="name" value="<?= $val('name') ?>" class="field" maxlength="120" required>
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-medium">Description <span class="font-normal text-muted">(optional)</span></label>
                <input type="text" id="description" name="description" value="<?= $val('description') ?>" class="field" maxlength="255">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="price" class="mb-1 block text-sm font-medium">Price (<?= e(CURRENCY) ?>)</label>
                    <input type="number" id="price" name="price" value="<?= $val('price') ?>" min="0" step="50" class="field" required>
                </div>
                <div>
                    <label for="duration_minutes" class="mb-1 block text-sm font-medium">Minutes</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" value="<?= $val('duration_minutes', '60') ?>" min="<?= SLOT_MINUTES ?>" max="480" step="<?= SLOT_MINUTES ?>" class="field" required>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-lg bg-secondary px-5 py-2.5 text-sm font-semibold text-white hover:bg-ink"><?= $editing ? 'Save changes' : 'Add service' ?></button>
                <?php if ($editing): ?><a href="<?= e(url('admin/services.php')) ?>" class="text-sm font-medium text-muted hover:text-secondary">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
