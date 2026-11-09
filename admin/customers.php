<?php
require_once __DIR__ . '/../includes/init.php';

$user = require_role('admin');

$q = trim((string) ($_GET['q'] ?? ''));
$sql = "SELECT u.*, (SELECT COUNT(*) FROM appointments a WHERE a.customer_id = u.id) AS appointment_count
        FROM users u WHERE u.role = 'customer'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$stmt = db()->prepare($sql . ' ORDER BY u.created_at DESC');
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle    = 'Customers';
$pageSubtitle = 'Everyone who has registered an account.';
$activePage   = 'customers';
require __DIR__ . '/../includes/dashboard_header.php';
?>

<form method="get" action="<?= e(url('admin/customers.php')) ?>" class="mb-5 flex max-w-lg gap-2">
    <label for="q" class="sr-only">Search customers</label>
    <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search by name, email or phone" class="field">
    <button type="submit" class="rounded-lg bg-secondary px-5 text-sm font-semibold text-white hover:bg-ink">Search</button>
</form>

<?php if ($customers): ?>
    <div class="table-wrap rounded-xl border border-line bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-xs uppercase tracking-wide text-muted">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email verified</th>
                    <th class="px-4 py-3">Appointments</th>
                    <th class="px-4 py-3">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium"><?= e($c['full_name']) ?></td>
                        <td class="px-4 py-3"><?= e($c['email']) ?></td>
                        <td class="px-4 py-3"><?= e($c['phone']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($c['email_verified_at']): ?>
                                <span class="text-emerald-700">Verified</span>
                            <?php else: ?>
                                <span class="text-amber-700">Not verified</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= (int) $c['appointment_count'] ?></td>
                        <td class="whitespace-nowrap px-4 py-3 text-muted"><?= e(date('j M Y', strtotime($c['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="rounded-xl border border-dashed border-line bg-white px-6 py-12 text-center">
        <p class="font-medium">No customers found</p>
        <p class="mt-1 text-sm text-muted"><?= $q !== '' ? 'Try a different search.' : 'Customers will appear here after they register.' ?></p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/dashboard_footer.php'; ?>
