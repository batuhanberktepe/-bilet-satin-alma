<?php
session_start();
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit;
}
if ($_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['trip_uuid'])) {
    header("Location: index.php");
    exit;
}
$trip_uuid = $_GET['trip_uuid'];
$trip = getTripDetails($pdo, $trip_uuid);
if (!$trip) { die("Hata: İstenen sefer bulunamadı. <a href='index.php'>Ana Sayfaya Dön</a>"); }
$booked_seats = getBookedSeats($pdo, $trip['id']);
$total_capacity = $trip['capacity'];


$error_message = '';
$success_message = '';
$coupon_code = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = $_POST['selected_seats'] ?? [];
    $coupon_code = trim($_POST['coupon_code'] ?? ''); 

    if (empty($selected_seats)) {
        $error_message = "Lütfen en az bir koltuk seçin.";
    } else {
        $current_booked_seats = getBookedSeats($pdo, $trip['id']);
        $already_taken = array_intersect($selected_seats, $current_booked_seats);

        if (!empty($already_taken)) {
            $error_message = "Üzgünüz, siz işlem yaparken " . implode(', ', $already_taken) . " numaralı koltuk(lar) başkası tarafından alındı. Lütfen tekrar seçin.";
            $booked_seats = $current_booked_seats;
        } else {
            $base_price = $trip['price'];
            $seat_count = count($selected_seats);
            $final_price = $seat_count * $base_price;
            $coupon_id = null;
            $discount_amount = 0;

            
            if (!empty($coupon_code)) {
                $coupon = findCoupon($pdo, $coupon_code);
                if ($coupon) {
                    $discount_amount = $final_price * ($coupon['discount'] / 100);
                    $final_price -= $discount_amount;
                    $coupon_id = $coupon['id'];
                } else {
                   
                    $error_message = "Gönderilen kupon kodu geçersiz veya süresi dolmuş. Lütfen kuponu kaldırıp tekrar deneyin veya geçerli bir kod girin.";
                }
            }

            
            if (empty($error_message)) {
                $result = processTicketPurchase(
                    $pdo,
                    $_SESSION['user_id'],
                    $trip['id'],
                    $selected_seats,
                    $final_price, 
                    $coupon_id
                );

                if ($result === true) {
                    $success_message = "Biletleriniz başarıyla oluşturuldu! Toplam Tutar: " . number_format($final_price, 2) . " TL. Hesabım sayfasına yönlendiriliyorsunuz...";
                    $booked_seats = getBookedSeats($pdo, $trip['id']);
                    header("Refresh: 5; url=account.php");
                } else {
                    $error_message = $result; 
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f0f8f0; margin: 0; padding: 20px; }
        .top-nav { background-color: #fff; padding: 10px 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); max-width: 900px; margin: -20px auto 20px auto; border-radius: 0 0 8px 8px; display: flex; justify-content: space-between; align-items: center; }
        .top-nav .logo { font-size: 1.8em; font-weight: bold; color: #2e7d32; text-decoration: none; }
        .top-nav a { color: #4CAF50; text-decoration: none; font-weight: bold; }
        .top-nav .user-info { display: flex; align-items: center; gap: 15px; }
        .container { max-width: 900px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .trip-info { background-color: #e8f5e9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .trip-info h2 { margin-top: 0; color: #2e7d32; }
        .seat-selection { display: flex; flex-wrap: wrap; gap: 20px; }
        .seat-map { flex: 2; min-width: 300px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .bus-front { grid-column: 1 / -1; text-align: center; font-weight: bold; color: #555; padding-bottom: 10px; border-bottom: 2px dashed #ccc; }
        .seat-map > div:nth-child(4n + 4) { grid-column-start: 3; }
        .seat-label { display: block; padding: 10px; background-color: #fff; border: 1px solid #ccc; border-radius: 5px; text-align: center; cursor: pointer; font-weight: bold; }
        .seat-checkbox { display: none; }
        .seat-checkbox:not(:disabled) + .seat-label:hover { background-color: #dceddc; border-color: #4CAF50; }
        .seat-checkbox:checked + .seat-label { background-color: #4CAF50; color: white; border-color: #2e7d32; }
        .seat-checkbox:disabled + .seat-label { background-color: #e0e0e0; color: #999; border-color: #ccc; cursor: not-allowed; text-decoration: line-through; }
        .checkout { flex: 1; min-width: 250px; }
        .checkout-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #eee; position: sticky; top: 20px; }
        .checkout-box h3 { margin-top: 0; }
        .checkout-box input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; }
        /* Readonly input için stil */
        .checkout-box input[readonly] { background-color: #eee; cursor: not-allowed; }
        .checkout-box button { width: 100%; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .checkout-box button:hover { background-color: #45a049; }
        .checkout-box button:disabled { background-color: #ccc; cursor: not-allowed; }
        #apply-coupon-btn { background-color: #555; font-size: 14px; padding: 8px; }
        #total-price { font-size: 1.5em; font-weight: bold; color: #2e7d32; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .error { background-color: #fdd; color: #e74c3c; }
        .success { background-color: #dff0d8; color: #27ae60; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="index.php" class="logo">BBT-Bilet</a>
        <div class="user-info">
            <span>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['user_fullname']) ?></strong>!</span>
            <a href="account.php">Hesabım</a>
            <span>Bakiye: <strong><?= number_format($_SESSION['user_balance'], 2) ?> TL</strong></span>
            <a href="logout.php">Çıkış Yap</a>
        </div>
    </div>

    <div class="container">

        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (!$success_message): ?>
        <form action="buy_ticket.php?trip_uuid=<?= htmlspecialchars($trip_uuid) ?>" method="POST">
            <div class="trip-info">
                <h2>Sefer Detayları</h2>
                <p>
                    <strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']) ?><br>
                    <strong>Güzergah:</strong> <?= htmlspecialchars($trip['departure_city']) ?> -> <?= htmlspecialchars($trip['destination_city']) ?><br>
                    <strong>Tarih:</strong> <?= date('d M Y', strtotime($trip['departure_time'])) ?><br>
                    <strong>Kalkış:</strong> <?= date('H:i', strtotime($trip['departure_time'])) ?> |
                    <strong>Varış:</strong> <?= date('H:i', strtotime($trip['arrival_time'])) ?><br>
                    <strong>Bilet Fiyatı:</strong> <span id="base-price"><?= htmlspecialchars($trip['price']) ?></span> TL
                </p>
            </div>

            <div class="seat-selection">
                <div class="seat-map">
                    <div class="bus-front">ŞOFÖR</div>
                    <?php for ($i = 1; $i <= $total_capacity; $i++): ?>
                        <?php $is_booked = in_array($i, $booked_seats); ?>
                        <div>
                            <input
                                type="checkbox"
                                name="selected_seats[]"
                                value="<?= $i ?>"
                                id="seat-<?= $i ?>"
                                class="seat-checkbox"
                                <?= $is_booked ? 'disabled' : '' ?>
                            >
                            <label for="seat-<?= $i ?>" class="seat-label"><?= $i ?></label>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="checkout">
                    <div class="checkout-box">
                        <h3>Ödeme</h3>
                        <p>
                            Seçilen Koltuklar: <strong id="selected-seats-display">Yok</strong><br>
                            İndirim: <strong id="discount-display">0.00 TL</strong>
                        </p>
                        <p>
                            Toplam Tutar:<br>
                            <span id="total-price">0.00 TL</span>
                        </p>
                        <hr>
                        <label for="coupon_code">Kupon Kodu (Varsa)</label>
                        <input type="text" name="coupon_code" id="coupon_code" placeholder="İndirim Kodu" value="<?= htmlspecialchars($coupon_code) ?>">
                        <button type="button" id="apply-coupon-btn">Kuponu Uygula</button>
                        <hr>
                        <button type="submit" id="buy-btn">Güvenli Ödeme Yap</button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const basePrice = parseFloat(document.getElementById('base-price').innerText);
    const seats = document.querySelectorAll('.seat-checkbox');
    const selectedDisplay = document.getElementById('selected-seats-display');
    const totalDisplay = document.getElementById('total-price');
    const discountDisplay = document.getElementById('discount-display');
    const couponInput = document.getElementById('coupon_code');
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    const buyBtn = document.getElementById('buy-btn');

    let currentDiscountPercent = 0.0;
    let currentSeatCount = 0;
    let couponAppliedSuccessfully = false;

    function updateTotals() {
        const selected = document.querySelectorAll('.seat-checkbox:checked');
        currentSeatCount = selected.length;
        if (currentSeatCount === 0) {
            selectedDisplay.innerText = 'Yok';
            buyBtn.disabled = true;
        } else {
            buyBtn.disabled = false;
            let seatNumbers = [];
            selected.forEach(seat => { seatNumbers.push(seat.value); });
            selectedDisplay.innerText = seatNumbers.join(', ');
        }
        let baseTotal = currentSeatCount * basePrice;
        let discountAmount = couponAppliedSuccessfully ? (baseTotal * (currentDiscountPercent / 100)) : 0;
        let finalTotal = baseTotal - discountAmount;
        discountDisplay.innerText = number_format(discountAmount, 2) + ' TL';
        totalDisplay.innerText = number_format(finalTotal, 2) + ' TL';
    }

    seats.forEach(seat => { seat.addEventListener('change', updateTotals); });

    applyCouponBtn.addEventListener('click', function() {
        const code = couponInput.value.trim();
        if (!code) { alert('Lütfen bir kupon kodu girin.'); return; }
        applyCouponBtn.disabled = true;
        applyCouponBtn.innerText = 'Kontrol ediliyor...';
        fetch('check_coupon.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ code: code }) })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentDiscountPercent = parseFloat(data.discount);
                couponAppliedSuccessfully = true;
                alert('Kupon başarıyla uygulandı! %' + data.discount + ' indirim kazandınız.');
                
                
                couponInput.readOnly = true; 
                

                applyCouponBtn.innerText = 'Kupon Uygulandı';
                
            } else {
                currentDiscountPercent = 0.0;
                couponAppliedSuccessfully = false;
                alert(data.message);
                couponInput.readOnly = false; 
                applyCouponBtn.disabled = false;
                applyCouponBtn.innerText = 'Kuponu Uygula';
            }
            updateTotals();
        })
        .catch(err => {
            alert('Kupon kontrolü sırasında bir hata oluştu.');
            console.error(err);
             couponInput.readOnly = false; 
           applyCouponBtn.disabled = false;
            applyCouponBtn.innerText = 'Kuponu Uygula';
        });
    });

    function number_format(number, decimals) {
        number = parseFloat(number);
        if (isNaN(number)) return '0.00';
        return number.toLocaleString('tr-TR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    }
    updateTotals();

    
    if(couponInput.value && !couponInput.disabled && !couponInput.readOnly){
         
        
    <?php if ($error_message && !empty($coupon_code)): ?>
        couponInput.readOnly = false;
        applyCouponBtn.disabled = false;
        applyCouponBtn.innerText = 'Kuponu Uygula';
    <?php endif; ?>

});
</script>

</body>
</html>