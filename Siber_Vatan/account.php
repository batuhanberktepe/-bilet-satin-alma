<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login_register.php"); exit; }
if ($_SESSION['user_role'] !== 'user' && $_SESSION['user_role'] !== 'company_admin') { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_balance'])) {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) { $message = "Lütfen geçerli bir tutar girin."; $message_type = 'error'; }
        else {
            if (addBalance($pdo, $user_id, $amount)) { $message = number_format($amount, 2) . " TL başarıyla hesabınıza eklendi."; $message_type = 'success'; }
            else { $message = "Bakiye yüklenirken bir hata oluştu."; $message_type = 'error'; }
        }
    }
    if (isset($_POST['cancel_ticket'])) {
        $ticket_id_to_cancel = (int)($_POST['ticket_id'] ?? 0);
        if ($ticket_id_to_cancel <= 0) { $message = "Geçersiz bilet ID'si."; $message_type = 'error'; }
        else {
             $result = cancelTicket($pdo, $ticket_id_to_cancel, $user_id);
             if ($result === true) { $message = "Biletiniz başarıyla iptal edildi ve tutarı bakiyenize iade edildi."; $message_type = 'success'; }
             else { $message = $result; $message_type = 'error'; }
        }
    }
}

$all_my_tickets = getUserTickets($pdo, $user_id);
$active_tickets = [];
$past_or_cancelled_tickets = [];
$current_timestamp = time();

foreach ($all_my_tickets as $ticket) {
    $departure_timestamp = strtotime($ticket['departure_time']);
    if ($ticket['status'] === 'active' && $departure_timestamp > $current_timestamp) {
        $active_tickets[] = $ticket;
    } else {
        $ticket['display_status'] = ($ticket['status'] === 'active' && $departure_timestamp <= $current_timestamp) ? 'expired' : $ticket['status'];
        $past_or_cancelled_tickets[] = $ticket;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım - BBT Bilet</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f8f0; color: #333; margin: 0; padding: 20px; }
        .top-nav { background-color: #fff; padding: 10px 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); max-width: 1200px; margin: -20px auto 20px auto; border-radius: 0 0 8px 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav .logo { font-size: 1.8em; font-weight: bold; color: #2e7d32; text-decoration: none; }
        .top-nav .user-info { display: flex; align-items: center; gap: 15px; }
        .top-nav a { color: #4CAF50; text-decoration: none; font-weight: bold; }
        .top-nav a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; } 
        h1 { color: #2e7d32; text-align: left; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .success { background-color: #dff0d8; color: #27ae60; }
        .error { background-color: #fdd; color: #e74c3c; }

        .account-layout-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px;
        }
        .tickets-widget { 
             grid-column: 1 / -1; 
             background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px;
        }
         .tickets-widget h2 { margin-top: 0; color: #2e7d32; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        .tickets-container { 
            display: flex;
            flex-wrap: wrap; 
            gap: 25px;
            margin-top: 15px;
        }
        .ticket-column { 
            flex: 1; 
            min-width: 350px; 
        }

        .widget { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); } 
        .widget h2 { margin-top: 0; color: #2e7d32; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .widget h3 { color: #388e3c; margin-top: 0px; margin-bottom: 15px; font-size: 1.3em; } 

        .profile-info p { font-size: 1.1em; margin: 10px 0; }
        .profile-info strong { color: #555; display: inline-block; width: 80px; }
        .balance-form { display: flex; flex-direction: column; gap: 10px; }
        .balance-form input[type="number"] { padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 1em; width: 100%; box-sizing: border-box; }
        .balance-form button { background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; font-weight: bold; padding: 12px 20px; width: 100%; }
        .balance-form button:hover { background-color: #45a049; }
        .ticket-list { display: flex; flex-direction: column; gap: 15px; }
        .ticket-card { border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; overflow: hidden; position: relative; }
        .ticket-main { flex: 3; padding: 15px 20px; min-width: 200px; font-size: 0.9em; }
        .ticket-main .route { font-size: 1.2em; font-weight: bold; color: #2e7d32; margin-bottom: 8px; }
        .ticket-main .company { font-weight: bold; color: #555; }
        .ticket-main .time { color: #333; font-size: 0.95em; }
        .ticket-details { flex: 1; background: #f9f9f9; padding: 15px 20px; min-width: 140px; border-left: 1px solid #eee; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-end; font-size: 0.9em; }
        .ticket-details .price { font-size: 1.3em; font-weight: bold; color: #2e7d32; margin-bottom: 8px; }
        .ticket-status { font-size: 0.85em; font-weight: bold; padding: 3px 8px; border-radius: 15px; color: #fff; align-self: flex-start; margin-bottom: 8px; }
        .status-active { background-color: #3498db; }
        .status-cancelled { background-color: #e74c3c; }
        .status-expired { background-color: #7f8c8d; }
        .no-tickets { text-align: center; padding: 20px; background-color: #f9f9f9; border-radius: 8px; font-style: italic; color: #777;}
        .ticket-actions { display: flex; gap: 5px; margin-top: 10px; }
        .cancel-button { background-color: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; font-size: 0.85em; font-weight: bold; display: block; }
        .cancel-button:hover { background-color: #c0392b; }
        .cancel-button:disabled { background-color: #bdc3c7; cursor: not-allowed; }
        .pdf-button { background-color: #2980b9; color: white; text-decoration: none; padding: 6px 10px; border-radius: 5px; font-size: 0.85em; font-weight: bold; display: block; }
        .pdf-button:hover { background-color: #2471a3; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="index.php" class="logo">BBT-Bilet</a>
        <div class="user-info">
            <span>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user_fullname'] ?? 'Misafir') ?></strong>!</span>
            
            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] == 'user' || $_SESSION['user_role'] == 'company_admin'): ?>
                    <a href="account.php" style="color: #e74c3c; font-weight: bold;">Hesabım</a>
                <?php endif; ?>
                 <?php if ($_SESSION['user_role'] == 'user'): ?>
                     <span>Bakiye: <strong><?= number_format($_SESSION['user_balance'] ?? 0, 2) ?> TL</strong></span>
                <?php elseif ($_SESSION['user_role'] == 'company_admin'): ?>
                    <a href="company_admin_panel.php">Firma Paneli</a>
                    <a href="coupon_management.php">Kupon Yönetimi</a>
                <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="admin_panel.php">Admin Paneli</a>
                    <a href="coupon_management.php">Kupon Yönetimi</a>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="logout.php">Çıkış Yap</a>
        </div>
    </div>

    <div class="container">
        <h1>Hesabım</h1>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="account-layout-grid">
            
            <div class="widget profile-info">
                <h2>Profil Bilgileri</h2>
                <p><strong>İsim:</strong> <?= htmlspecialchars($_SESSION['user_fullname']) ?></p>
                <p><strong>E-posta:</strong> <?= htmlspecialchars($_SESSION['user_email']) ?></p>
                <?php if($_SESSION['user_role'] === 'company_admin' && !empty($_SESSION['user_company_id'])): 
                    $admin_company = getCompanyDetails($pdo, $_SESSION['user_company_id']);
                    if($admin_company): ?>
                    <p><strong>Firma:</strong> <?= htmlspecialchars($admin_company['name']) ?></p>
                <?php endif; endif; ?>
            </div>

            <div class="widget balance-widget">
                <h2>Bakiye Yönetimi</h2>
                <p style="font-size: 1.5em; font-weight: bold; color: #2e7d32; margin-top: 0;"> Mevcut Bakiye: <?= number_format($_SESSION['user_balance'] ?? 0, 2) ?> TL </p>
                <form action="account.php" method="POST" class="balance-form">
                    <div class="form-group"> <input type="number" name="amount" min="1" step="0.01" placeholder="Yüklenecek Tutar" required> </div>
                    <button type="submit" name="add_balance">Bakiye Yükle</button>
                </form>
            </div>

            <div class="tickets-widget">
                 <h2>Biletlerim</h2>
                 <div class="tickets-container"> <div class="ticket-column">
                        <h3>Aktif Biletler</h3>
                        <div class="ticket-list">
                            <?php if (empty($active_tickets)): ?>
                                <p class="no-tickets">Aktif biletiniz bulunmamaktadır.</p>
                            <?php else: ?>
                                <?php foreach ($active_tickets as $ticket): ?>
                                    <?php
                                        $can_cancel = false;
                                        $departure_timestamp = strtotime($ticket['departure_time']);
                                        $time_diff_hours = ($departure_timestamp - time()) / 3600;
                                        if ($time_diff_hours > 1) { $can_cancel = true; }
                                    ?>
                                    <div class="ticket-card">
                                        <div class="ticket-main">
                                             <div class="route"> <?= htmlspecialchars($ticket['departure_city']) ?> &rarr; <?= htmlspecialchars($ticket['destination_city']) ?> </div>
                                             <span class="company"><?= htmlspecialchars($ticket['company_name']) ?></span>
                                             <div class="time"> <?= date('d M Y, H:i', strtotime($ticket['departure_time'])) ?> </div>
                                        </div>
                                        <div class="ticket-details">
                                            <div>
                                                <span class="ticket-status status-active"> active </span>
                                                <div class="price"><?= number_format($ticket['total_price'], 2) ?> TL</div>
                                            </div>
                                            <div class="ticket-actions">
                                                <?php if ($can_cancel): ?>
                                                    <form action="account.php" method="POST" onsubmit="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Tutar bakiyenize iade edilecektir.');" style="margin:0;">
                                                        <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                                                        <button type="submit" name="cancel_ticket" class="cancel-button">İptal Et</button>
                                                    </form>
                                                 <?php else: ?>
                                                     <button class="cancel-button" disabled title="Sefere 1 saatten az kaldığı için iptal edilemez.">İptal Edilemez</button>
                                                 <?php endif; ?>
                                                 <a href="generate_pdf.php?ticket_uuid=<?= $ticket['ticket_uuid'] ?>" target="_blank" class="pdf-button">PDF</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ticket-column">
                        <h3>Geçmiş ve İptal Edilenler</h3>
                         <div class="ticket-list">
                            <?php if (empty($past_or_cancelled_tickets)): ?>
                                <p class="no-tickets">Geçmiş veya iptal edilmiş biletiniz yok.</p>
                            <?php else: ?>
                                <?php foreach ($past_or_cancelled_tickets as $ticket): ?>
                                    <?php $status_class = 'status-' . htmlspecialchars($ticket['display_status']); ?>
                                    <div class="ticket-card">
                                         <div class="ticket-main">
                                             <div class="route"> <?= htmlspecialchars($ticket['departure_city']) ?> &rarr; <?= htmlspecialchars($ticket['destination_city']) ?> </div>
                                             <span class="company"><?= htmlspecialchars($ticket['company_name']) ?></span>
                                             <div class="time"> <?= date('d M Y, H:i', strtotime($ticket['departure_time'])) ?> </div>
                                         </div>
                                         <div class="ticket-details">
                                            <div>
                                                <span class="ticket-status <?= $status_class ?>"> <?= htmlspecialchars($ticket['display_status']) ?> </span>
                                                <div class="price"><?= number_format($ticket['total_price'], 2) ?> TL</div>
                                            </div>
                                            <div class="ticket-actions">
                                                <a href="generate_pdf.php?ticket_uuid=<?= $ticket['ticket_uuid'] ?>" target="_blank" class="pdf-button">PDF</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                 </div> </div> </div> </div>

</body>
</html>