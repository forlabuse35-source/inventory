<?php
$pageTitle = 'Users';
require_once __DIR__ . '/includes/header.php';
requireAdmin();

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'staff';
        $status   = $_POST['status'] ?? 'active';

        if ($username === '' || $email === '' || $fullName === '' || $password === '') {
            setFlash('error', 'All fields are required.');
        } elseif (strlen($password) < 6) {
            setFlash('error', 'Password must be at least 6 characters.');
        } elseif ($role === 'admin' && !canCreateAdmin()) {
            setFlash('error', 'Maximum admin accounts (' . MAX_ADMIN_ACCOUNTS . ') reached.');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare('INSERT INTO users (username, email, password, full_name, role, status) VALUES (:u, :e, :p, :fn, :r, :s)');
                $stmt->execute([
                    ':u'  => $username,
                    ':e'  => $email,
                    ':p'  => $hash,
                    ':fn' => $fullName,
                    ':r'  => $role,
                    ':s'  => $status,
                ]);
                setFlash('success', 'User created successfully.');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setFlash('error', 'Username or email already exists.');
                } else {
                    setFlash('error', 'Failed to create user.');
                }
            }
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'edit') {
        $id       = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'staff';
        $status   = $_POST['status'] ?? 'active';

        if ($id <= 0 || $username === '' || $email === '' || $fullName === '') {
            setFlash('error', 'All fields are required.');
        } else {
            // Check admin limit when promoting to admin
            $currentRole = $db->prepare('SELECT role FROM users WHERE id = :id');
            $currentRole->execute([':id' => $id]);
            $existingRole = $currentRole->fetchColumn();

            if ($role === 'admin' && $existingRole !== 'admin' && !canCreateAdmin()) {
                setFlash('error', 'Maximum admin accounts (' . MAX_ADMIN_ACCOUNTS . ') reached.');
            } else {
                try {
                    if ($password !== '') {
                        if (strlen($password) < 6) {
                            setFlash('error', 'Password must be at least 6 characters.');
                            header('Location: users.php');
                            exit;
                        }
                        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                        $stmt = $db->prepare('UPDATE users SET username = :u, email = :e, password = :p, full_name = :fn, role = :r, status = :s WHERE id = :id');
                        $stmt->execute([':u' => $username, ':e' => $email, ':p' => $hash, ':fn' => $fullName, ':r' => $role, ':s' => $status, ':id' => $id]);
                    } else {
                        $stmt = $db->prepare('UPDATE users SET username = :u, email = :e, full_name = :fn, role = :r, status = :s WHERE id = :id');
                        $stmt->execute([':u' => $username, ':e' => $email, ':fn' => $fullName, ':r' => $role, ':s' => $status, ':id' => $id]);
                    }
                    setFlash('success', 'User updated successfully.');
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        setFlash('error', 'Username or email already exists.');
                    } else {
                        setFlash('error', 'Failed to update user.');
                    }
                }
            }
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            setFlash('error', 'You cannot delete your own account.');
        } elseif ($id > 0) {
            try {
                $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
                setFlash('success', 'User deleted successfully.');
            } catch (PDOException $e) {
                setFlash('error', 'Cannot delete user. They may have associated records.');
            }
        }
        header('Location: users.php');
        exit;
    }
}

// Fetch users
$users = $db->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
$adminCount = getAdminCount();
?>

<div class="card">
    <div class="card-header">
        <div class="toolbar">
            <div class="search-input">
                <i class="fas fa-search"></i>
                <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <span class="badge badge-info" style="font-size: 0.8rem;">Admins: <?= $adminCount ?>/<?= MAX_ADMIN_ACCOUNTS ?></span>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                <i class="fas fa-user-plus"></i> Add User
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No users found.</p>
            </div>
        <?php else: ?>
        <table class="table" id="usersTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= sanitize($u['full_name']) ?></strong></td>
                    <td><?= sanitize($u['username']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td><?= getRoleBadge($u['role']) ?></td>
                    <td><?= getStatusBadge($u['status']) ?></td>
                    <td><?= formatDate($u['created_at']) ?></td>
                    <td class="actions">
                        <button class="btn btn-sm btn-outline" onclick='editUser(<?= json_encode($u) ?>)'>
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return false;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmAction('Delete this user?', () => this.closest('form').submit())">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Add New User</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" id="addUserForm">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="johndoe" required>
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                    <div class="form-error"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="staff">Staff</option>
                            <option value="admin" <?= !canCreateAdmin() ? 'disabled' : '' ?>>
                                Admin <?= !canCreateAdmin() ? '(Limit reached)' : '' ?>
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST" id="editUserForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editUserId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" id="editUserFullName" class="form-control" required>
                        <div class="form-error"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="editUserUsername" class="form-control" required>
                        <div class="form-error"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="editUserEmail" class="form-control" required>
                    <div class="form-error"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter new password" minlength="6">
                    <div class="form-error"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" id="editUserRole" class="form-control" required>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" id="editUserStatus" class="form-control" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Dialog -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Are you sure?</h3>
        <p id="confirmMessage">This action cannot be undone.</p>
        <div class="confirm-actions">
            <button class="btn btn-secondary" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-danger" id="confirmYes">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('editUserId').value       = user.id;
    document.getElementById('editUserFullName').value  = user.full_name;
    document.getElementById('editUserUsername').value   = user.username;
    document.getElementById('editUserEmail').value      = user.email;
    document.getElementById('editUserRole').value       = user.role;
    document.getElementById('editUserStatus').value     = user.status;
    openModal('editUserModal');
}

initTableSearch('userSearch', 'usersTable');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
