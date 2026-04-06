<?php
$is_admin = ($_SESSION['tech_position'] ?? '') === 'Admin';
$dir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
$base_path = ($dir === 'admin' || $dir === 'tech') ? '../' : '';
$current_page = basename($_SERVER['PHP_SELF']);
$is_tech = in_array($current_page, ['technical.php', 'tech-ticket.php']);

// Get pending tickets count
$pending_count = 0;
// Privilege defaults (all shown)
$priv_tickets   = 1;
$priv_technical = 1;
$priv_reports   = 1;

if (isset($pdo)) {
    try {
        if ($is_admin) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE status IN ('Pending', 'In Progress', 'Assigned') AND is_viewed = 0");
        } else {
            $tech_id_query = $_SESSION['tech_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE status IN ('Pending', 'In Progress', 'Assigned') AND is_viewed = 0 AND technical_id = ?");
            $stmt->execute([$tech_id_query]);
        }
        $pending_count = $stmt->fetch()['count'] ?? 0;

        // Resolved ticket notification count (unread)
        if ($is_admin) {
            $rStmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'Resolved' AND is_viewed = 0 AND technical_id IS NOT NULL");
            $resolved_notif_count = $rStmt->fetch()['count'] ?? 0;
        } else {
            $resolved_notif_count = 0;
        }

        // Read privilege columns for non-admin technicians (with migration guard)
        if (!$is_admin && !empty($_SESSION['tech_id'])) {
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM technical_staff LIKE 'can_view_tickets'")->fetchColumn();
                if ($colCheck) {
                    $pStmt = $pdo->prepare("SELECT can_view_tickets, can_view_technical, can_view_reports FROM technical_staff WHERE technical_id = ?");
                    $pStmt->execute([$_SESSION['tech_id']]);
                    $privRow = $pStmt->fetch();
                    if ($privRow) {
                        $priv_tickets   = (int) $privRow['can_view_tickets'];
                        $priv_technical = (int) $privRow['can_view_technical'];
                        $priv_reports   = (int) $privRow['can_view_reports'];
                    }
                }
            } catch (Exception $e) {
                // Columns not yet added — keep defaults (all visible)
            }
        }
    } catch (Exception $e) {
        $pending_count = 0;
        $resolved_notif_count = 0;
    }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@800;900&display=swap" rel="stylesheet">
<nav class="navbar">
    <div class="logo">
        <img src="<?= $base_path ?>logo_mssc.png" alt="Logo">
    </div>
    <div class="nav-links">
        <?php if ($is_admin): ?>
            <a href="<?= $base_path ?>admin/index.php"
                class="nav-link <?= ($current_page == 'index.php' && $dir == 'admin') ? 'active' : '' ?>"><i
                    class="fas fa-home"></i> <span>Dashboard</span></a>
            <a href="<?= $base_path ?>admin/new-ticket.php"
                class="nav-link <?= ($current_page == 'new-ticket.php') ? 'active' : '' ?>"><i
                    class="fas fa-plus-circle"></i> <span>New Ticket</span></a>
            <a href="<?= $base_path ?>admin/tickets.php"
                class="nav-link <?= ($current_page == 'tickets.php') ? 'active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-list"></i>
                    <?php if ($pending_count > 0): ?>
                        <span class="nav-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </div>
                <span>Tickets</span>
            </a>
            <a href="<?= $base_path ?>admin/technical.php" class="nav-link <?= $is_tech ? 'active' : '' ?>"><i
                    class="fas fa-user-cog"></i> <span>Technical</span></a>
            <a href="<?= $base_path ?>admin/reports.php"
                class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i>
                <span>Reports</span></a>
            <!-- Bell: resolved ticket notifications -->
            <a href="<?= $base_path ?>admin/notifications.php"
                class="nav-link <?= ($current_page == 'notifications.php') ? 'active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-bell"></i>
                    <span class="nav-badge" id="resolvedNotifBadge" <?= $resolved_notif_count <= 0 ? 'style="display:none;"' : '' ?>>
                        <?= $resolved_notif_count ?>
                    </span>
                </div>
                <span>Notifications</span>
            </a>
        <?php else: ?>
            <a href="<?= $base_path ?>tech/index.php"
                class="nav-link <?= ($current_page == 'index.php' && $dir == 'tech') ? 'active' : '' ?>">
                <div class="nav-icon-wrapper">
                    <i class="fas fa-home"></i>
                    <?php if ($pending_count > 0): ?>
                        <span class="nav-badge" id="techNavBadge"><?= $pending_count ?></span>
                    <?php else: ?>
                        <span class="nav-badge" id="techNavBadge" style="display: none;">0</span>
                    <?php endif; ?>
                </div>
                <span>My Dashboard</span>
            </a>

            <?php if ($priv_tickets): ?>
            <a href="<?= $base_path ?>admin/tickets.php"
                class="nav-link <?= ($current_page == 'tickets.php') ? 'active' : '' ?>">
                <i class="fas fa-list"></i>
                <span>Tickets</span>
            </a>
            <?php endif; ?>

            <?php if ($priv_technical): ?>
            <a href="<?= $base_path ?>admin/technical.php"
                class="nav-link <?= $is_tech ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i>
                <span>Technical</span>
            </a>
            <?php endif; ?>

            <?php if ($priv_reports): ?>
            <a href="<?= $base_path ?>admin/reports.php"
                class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <?php endif; ?>

            <div class="dark-logout">
                <a href="#" id="themeToggleBtn" class="nav-link"><i class="fas fa-moon"></i> <span>Dark Mode</span></a>
                <a href="<?= $base_path ?>tech/account-settings.php"
                    class="nav-link <?= ($current_page == 'account-settings.php') ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Account Settings</span>
                </a>
            <?php endif; ?>
            <a href="#" onclick="confirmLogout(event, '<?= $base_path ?>logout.php')" class="nav-link"
                style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>

    </div>
</nav>

<!-- Global Logout Confirmation Modal -->
<div class="modal" id="logoutConfirmModal">
    <div class="modal-content logout-modal-content">
        <button class="modal-close logout-modal-close" onclick="closeLogoutModal()">&times;</button>

        <div class="logout-modal-icon-wrap">
            <div class="logout-modal-icon-ring">
                <i class="fas fa-sign-out-alt"></i>
            </div>
        </div>

        <h2 class="logout-modal-title">Confirm Logout</h2>
        <p class="logout-modal-desc">Are you sure you want to sign out of your account?</p>

        <div class="logout-modal-actions">
            <button class="btn btn-secondary logout-modal-btn" onclick="closeLogoutModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <a href="#" id="confirmLogoutBtn" class="btn btn-danger logout-modal-btn">
                <i class="fas fa-sign-out-alt"></i> Yes, Logout
            </a>
        </div>
    </div>
</div>

<script>
    function confirmLogout(event, logoutUrl) {
        if (event) event.preventDefault();
        const modal = document.getElementById('logoutConfirmModal');
        const confirmBtn = document.getElementById('confirmLogoutBtn');

        if (confirmBtn && logoutUrl) {
            confirmBtn.href = logoutUrl;
        }

        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Global window click to close modals (doesn't hurt if duplicated in script.js as it checks target)
    window.addEventListener('click', function (event) {
        const modal = document.getElementById('logoutConfirmModal');
        if (event.target === modal) {
            closeLogoutModal();
        }
    });

    // Theme Toggle Logic
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        if (!themeToggleBtn) return;
        const themeIcon = themeToggleBtn.querySelector('i');
        const themeText = themeToggleBtn.querySelector('span');

        // Retrieve saved theme or default to light
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        let currentTheme = 'light';

        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            currentTheme = 'dark';
        }

        // Apply on load
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateToggleUI(currentTheme);

        // Toggle click event
        themeToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', currentTheme);
            localStorage.setItem('theme', currentTheme);
            updateToggleUI(currentTheme);
        });

        function updateToggleUI(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeText.textContent = 'Dark Mode';
            }
        }
    });
</script>

<script>
    // Tiny inline script pushed as high as possible to prevent FOUC within body
    (function () {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>

<!-- Notification Polling Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Poll every 10 seconds for unread notifications
        setInterval(() => {
            fetch('<?= $base_path ?><?= $is_admin ? "admin" : "tech" ?>/get-unread-notifications.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update ticket nav badge
                        const navBadge = document.querySelector('.nav-badge');
                        if (navBadge) {
                            navBadge.textContent = data.total_unread;
                            navBadge.style.display = data.total_unread > 0 ? 'flex' : 'none';
                        }

                        // Update resolved notification badge (admin)
                        const notifBadge = document.getElementById('resolvedNotifBadge');
                        if (notifBadge) {
                            const count = data.resolved_unread ?? 0;
                            notifBadge.textContent = count;
                            notifBadge.style.display = count > 0 ? 'flex' : 'none';
                        }

                        // Update Company Group Headers if on Tech Dashboard
                        if (typeof window.updateCompanyBadges === 'function') {
                            window.updateCompanyBadges(data.companies || {});
                        }
                    }
                })
                .catch(err => console.error('Error fetching notifications:', err));
        }, 10000);
    });
</script>