<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login_register.php"); exit; }
if ($_SESSION['user_role'] !== 'admin') { header("Location: index.php"); exit; }

$search_term = trim($_GET['search'] ?? '');

$user_message = ''; $user_message_type = 'success';
$company_message = ''; $company_message_type = 'success';
$delete_message = ''; $delete_message_type = 'success'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {  }
    if (isset($_POST['add_company'])) {  }
    if (isset($_POST['delete_company'])) {
        $company_id_to_delete = (int)($_POST['company_id'] ?? 0);
        if ($company_id_to_delete > 0) {
            if (deleteCompany($pdo, $company_id_to_delete)) {
                $delete_message = "Firma ve ilişkili tüm seferler başarıyla silindi.";
                $delete_message_type = 'success';
            } else {
                $delete_message = "Firma silinirken bir hata oluştu veya firma bulunamadı.";
                $delete_message_type = 'error';
            }
        } else {
             $delete_message = "Geçersiz Firma ID'si.";
             $delete_message_type = 'error';
        }
    }
    if (isset($_POST['update_user'])) {
        $user_id_to_update = $_POST['user_id']; $new_role = $_POST['role']; $company_id = $_POST['company_id'] ?? null;
        if ($user_id_to_update == $_SESSION['user_id']) { $user_message = "Hata: Kendi rolünüzü buradan değiştiremezsiniz."; $user_message_type = 'error'; }
        else { $result = updateUserRoleAndCompany($pdo, $user_id_to_update, $new_role, (int)$company_id);
            if ($result === true) { $user_message = "Kullanıcı başarıyla güncellendi."; $user_message_type = 'success'; }
            else { $user_message = $result; $user_message_type = 'error'; }
        }
    }
     if (isset($_POST['add_company'])) {
        $company_name = trim($_POST['company_name'] ?? '');
        if (empty($company_name)) { $company_message = "Firma adı boş olamaz."; $company_message_type = 'error'; }
        else { $result = createCompany($pdo, $company_name);
            if ($result === true) { $company_message = "Firma başarıyla eklendi. Liste güncellendi."; $company_message_type = 'success'; }
            else { $company_message = $result; $company_message_type = 'error'; }
        }
    }
}

$users = getAllUsers($pdo, $_SESSION['user_id'], $search_term);
$companies = getAllCompanies($pdo); 
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Yönetim</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f8f0; color: #333; margin: 0; padding: 20px; }
        .top-nav { background-color: #fff; padding: 10px 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); max-width: 1200px; margin: -20px auto 20px auto; border-radius: 0 0 8px 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav .logo { font-size: 1.8em; font-weight: bold; color: #2e7d32; text-decoration: none; }
        .top-nav .user-info { display: flex; align-items: center; gap: 15px; }
        .top-nav a { color: #4CAF50; text-decoration: none; font-weight: bold; }
        .top-nav a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h1 { color: #2e7d32; text-align: left; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .success { background-color: #dff0d8; color: #27ae60; }
        .error { background-color: #fdd; color: #e74c3c; }
        .management-section { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #eee; }
        .management-section h2 { margin-top: 0; color: #2e7d32; border-bottom: 1px solid #dceddc; padding-bottom: 10px; }
        .company-form { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .company-form .form-group { flex: 1; min-width: 250px; }
        .company-form label { display: block; margin-bottom: 5px; font-weight: bold; }
        .company-form input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; height: 40px; }
        .company-form button { padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; height: 40px; }
        .search-user-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-user-form input[type="search"] { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; }
        .search-user-form button { padding: 10px 20px; background-color: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #e8f5e9; color: #2e7d32; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table select, .data-table button, .data-table a { padding: 8px 12px; border-radius: 5px; border: 1px solid #ccc; font-size: 0.9em; text-decoration: none; margin-right: 5px; display: inline-block; }
        .data-table button, .data-table a.button { background-color: #4CAF50; color: white; cursor: pointer; border: none; }
        .data-table a.edit-button { background-color: #f39c12; color: white; }
        .data-table button.delete-button { background-color: #e74c3c; color: white; }
        .data-table button:hover, .data-table a.button:hover { opacity: 0.8; }
        .data-table .current-role { font-weight: bold; }
        .data-table .action-buttons form { display: inline; margin: 0;} 
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="index.php" class="logo">BBT-Bilet</a>
        <div class="user-info">
            <span>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user_fullname'] ?? 'Misafir') ?></strong>!</span>
            <a href="admin_panel.php" style="color: #e74c3c; font-weight: bold;">Admin Paneli</a>
            <a href="coupon_management.php">Kupon Yönetimi</a>
            <a href="logout.php">Çıkış Yap</a>
        </div>
    </div>

    <div class="container">
        <h1>Admin Paneli</h1>

        <div class="management-section">
            <h2>Firma Yönetimi</h2>
            <?php if ($company_message): ?> <div class="message <?= $company_message_type ?>"><?= htmlspecialchars($company_message) ?></div> <?php endif; ?>
            <?php if ($delete_message): ?> <div class="message <?= $delete_message_type ?>"><?= htmlspecialchars($delete_message) ?></div> <?php endif; ?>

            <form action="admin_panel.php" method="POST" class="company-form" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                <div class="form-group">
                    <label for="company_name">Yeni Firma Adı:</label>
                    <input type="text" id="company_name" name="company_name" required placeholder="Firma Adı">
                </div>
                <button type="submit" name="add_company">Yeni Firma Ekle</button>
            </form>

            <table class="data-table company-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Firma Adı</th>
                        <th>Logo Yolu</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($companies)): ?>
                        <tr><td colspan="4" style="text-align: center;">Kayıtlı firma bulunmamaktadır.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?= $company['id'] ?></td>
                            <td><?= htmlspecialchars($company['name']) ?></td>
                            <td><?= htmlspecialchars($company['logo_path'] ?? '-') ?></td>
                            <td class="action-buttons">
                                <a href="edit_company.php?company_id=<?= $company['id'] ?>" class="edit-button">Düzenle</a>
                                <form action="admin_panel.php" method="POST" onsubmit="return confirm('DİKKAT! Bu firmayı silmek, firmaya ait TÜM SEFERLERİ de kalıcı olarak silecektir! Emin misiniz?');">
                                    <input type="hidden" name="company_id" value="<?= $company['id'] ?>">
                                    <button type="submit" name="delete_company" class="delete-button">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="management-section">
            <h2>Kullanıcı Yönetimi</h2>
            <form action="admin_panel.php" method="GET" class="search-user-form">
                <input type="search" name="search" placeholder="İsim veya Soyisime Göre Ara..." value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit">Ara</button>
                 <?php if (!empty($search_term)): ?>
                    <a href="admin_panel.php" style="padding: 10px 15px; background-color: #7f8c8d; color: white; text-decoration: none; border-radius: 5px; font-size: 1em;">Temizle</a>
                <?php endif; ?>
           </form>
            <?php if ($user_message): ?> <div class="message <?= $user_message_type ?>"><?= htmlspecialchars($user_message) ?></div> <?php endif; ?>

            <table class="data-table user-table"> <thead> <tr> <th>Ad Soyad</th> <th>E-posta</th> <th>Mevcut Rol / Firma</th> <th>Rol / Firma Ata</th> <th>İşlem</th> </tr> </thead>
                <tbody>
                    <?php if (empty($users) && !empty($search_term)): ?>
                         <tr><td colspan="5" style="text-align: center;">"<?= htmlspecialchars($search_term) ?>" ile eşleşen kullanıcı bulunamadı.</td></tr>
                    <?php elseif (empty($users)): ?>
                         <tr><td colspan="5" style="text-align: center;">Yönetilecek kullanıcı bulunmamaktadır.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td> <strong class="current-role"><?= htmlspecialchars($user['role']) ?></strong> <?php if ($user['company_name']): ?> <br><small>(<?= htmlspecialchars($user['company_name']) ?>)</small> <?php endif; ?> </td>
                            <td>
                                <form action="admin_panel.php<?= !empty($search_term) ? '?search=' . urlencode($search_term) : '' ?>" method="POST" id="form-<?= $user['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role" onchange="toggleCompanySelect('form-<?= $user['id'] ?>')">
                                        <option value="user" <?= ($user['role'] == 'user') ? 'selected' : '' ?>>User</option>
                                        <option value="company_admin" <?= ($user['role'] == 'company_admin') ? 'selected' : '' ?>>Firma Admin</option>
                                        <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <br>
                                    <select name="company_id" class="company-select" style="<?= ($user['role'] !== 'company_admin') ? 'display:none; margin-top:5px;' : 'margin-top:5px;' ?>">
                                        <option value="">Firma Seçin...</option>
                                        <?php foreach ($companies as $company): ?>
                                            <option value="<?= $company['id'] ?>" <?= ($user['company_id'] == $company['id']) ? 'selected' : '' ?>> <?= htmlspecialchars($company['name']) ?> </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td> <button type="submit" name="update_user" form="form-<?= $user['id'] ?>">Güncelle</button> </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<script> function toggleCompanySelect(formId) { const form = document.getElementById(formId); const roleSelect = form.querySelector('select[name="role"]'); const companySelect = form.querySelector('select[name="company_id"]'); if (roleSelect.value === 'company_admin') { companySelect.style.display = 'inline-block'; companySelect.required = true; } else { companySelect.style.display = 'none'; companySelect.required = false; companySelect.value = ''; } } </script>
</body>
</html>