<?php
session_start();
require_once 'functions.php';

$all_cities = ['Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Amasya', 'Ankara', 'Antalya', 'Artvin', 'Aydın', 'Balıkesir', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkâri', 'Hatay', 'Isparta', 'Mersin', 'İstanbul', 'İzmir', 'Kars', 'Kastamonu', 'Kayseri', 'Kırklareli', 'Kırşehir', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Kahramanmaraş', 'Mardin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Rize', 'Sakarya', 'Samsun', 'Siirt', 'Sinop', 'Sivas', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Şanlıurfa', 'Uşak', 'Van', 'Yozgat', 'Zonguldak', 'Aksaray', 'Bayburt', 'Karaman', 'Kırıkkale', 'Batman', 'Şırnak', 'Bartın', 'Ardahan', 'Iğdır', 'Yalova', 'Karabük', 'Kilis', 'Osmaniye', 'Düzce'];
sort($all_cities);

$trips = [];
$all_upcoming_trips = [];
$form_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_trips'])) {
    $form_submitted = true;
    $departure_city = trim($_POST['departure_city'] ?? '');
    $destination_city = trim($_POST['destination_city'] ?? '');
    $trip_date = trim($_POST['trip_date'] ?? '');

    if (!empty($departure_city) && !empty($destination_city) && !empty($trip_date)) {
        $trips = searchTrips($pdo, $departure_city, $destination_city, $trip_date);
    }
} else {
    $form_submitted = false;
    $all_upcoming_trips = getAllUpcomingTrips($pdo, 20);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBT-Bilet - Ana Sayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4">
        <div class="container" style="max-width: 900px;">
            <a class="navbar-brand fw-bold fs-4" href="index.php" style="color: #2e7d32;">BBT-Bilet</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="navbar-text me-3">
                                Hoş geldin, <strong class="text-dark"><?= htmlspecialchars($_SESSION['user_fullname'] ?? 'Misafir') ?></strong>!
                            </span>
                        </li>
                        
                        <?php if (isset($_SESSION['user_role'])): ?>
                            <?php if ($_SESSION['user_role'] == 'user'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="account.php">Hesabım</a>
                                </li>
                                <li class="nav-item">
                                    <span class="navbar-text me-3">Bakiye: <strong class="text-success"><?= number_format($_SESSION['user_balance'] ?? 0, 2) ?> TL</strong></span>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="admin_panel.php">Admin Paneli</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger fw-bold" href="coupon_management.php">Kupon Yönetimi</a>
                                </li>
                            <?php elseif ($_SESSION['user_role'] == 'company_admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="account.php">Hesabım</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="company_admin_panel.php">Firma Paneli</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link text-danger fw-bold" href="coupon_management.php">Kupon Yönetimi</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Çıkış Yap</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login_register.php">Giriş Yap / Kayıt Ol</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container bg-white p-4 p-md-5 rounded shadow-sm" style="max-width: 900px;">
        <h1 class="text-center" style="color: #2e7d32;">Sefer Arama</h1>
        <p class="text-center text-muted mb-4">Gideceğiniz yeri ve tarihi seçerek biletinizi kolayca bulun.</p>
        
        <form action="index.php" method="POST" class="mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md">
                    <label for="departure_city" class="form-label fw-bold">Nereden</label>
                    <input list="cities" class="form-control" id="departure_city" name="departure_city" placeholder="Kalkış şehri yazın..." required value="<?= htmlspecialchars($_POST['departure_city'] ?? '') ?>">
                </div>
                <div class="col-md">
                    <label for="destination_city" class="form-label fw-bold">Nereye</label>
                    <input list="cities" class="form-control" id="destination_city" name="destination_city" placeholder="Varış şehri yazın..." required value="<?= htmlspecialchars($_POST['destination_city'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="trip_date" class="form-label fw-bold">Tarih</label>
                    <input type="date" class="form-control" id="trip_date" name="trip_date" value="<?= htmlspecialchars($_POST['trip_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" name="search_trips" class="btn btn-success w-100">Sefer Ara</button>
                </div>
            </div>
            <datalist id="cities">
                <?php foreach ($all_cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>">
                <?php endforeach; ?>
            </datalist>
        </form>

        <div class="search-results mt-5">
            <?php if ($form_submitted): ?>
                <h2 class="fs-3 mb-3 border-bottom pb-2">Arama Sonuçları</h2>
                <?php if (!empty($trips)): ?>
                    <?php foreach ($trips as $trip): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-3 fw-bold fs-5"><?= htmlspecialchars($trip['company_name']) ?></div>
                                    <div class="col-md-4">
                                        <strong>Kalkış:</strong> <?= date('H:i', strtotime($trip['departure_time'])) ?> <br>
                                        <strong>Varış:</strong> <?= date('H:i', strtotime($trip['arrival_time'])) ?>
                                    </div>
                                    <div class="col-md-2 text-md-end">
                                        <span class="fs-4 fw-bold" style="color: #2e7d32;"><?= htmlspecialchars(number_format($trip['price'], 2, ',', '.')) ?> TL</span>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <a href="trip_details.php?trip_uuid=<?= $trip['uuid'] ?>" class="btn btn-primary w-100 w-md-auto">Detayları Gör</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Aradığınız kriterlere uygun sefer bulunamadı.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="upcoming-trips">
                    <h2 class="fs-3 mb-3 border-bottom pb-2">Yaklaşan Seferler</h2>
                    <?php if (!empty($all_upcoming_trips)): ?>
                        <?php foreach ($all_upcoming_trips as $trip): ?>
                             <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center g-3">
                                        <div class="col-md-5">
                                            <span class="fw-bold fs-5"><?= htmlspecialchars($trip['company_name']) ?></span><br>
                                            <span class="text-success fw-bold">
                                                <?= htmlspecialchars($trip['departure_city']) ?> &rarr; <?= htmlspecialchars($trip['destination_city']) ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Tarih:</strong> <?= date('d M Y, H:i', strtotime($trip['departure_time'])) ?>
                                        </div>
                                        <div class="col-md-2 text-md-end">
                                            <span class="fs-4 fw-bold" style="color: #2e7d32;"><?= htmlspecialchars(number_format($trip['price'], 2, ',', '.')) ?> TL</span>
                                        </div>
                                        <div class="col-md-2 text-md-end">
                                            <a href="trip_details.php?trip_uuid=<?= $trip['uuid'] ?>" class="btn btn-primary w-100 w-md-auto">Detayları Gör</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">Gösterilecek yaklaşan sefer bulunmamaktadır.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>