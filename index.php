<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// ==========================================
// أولاً: كود تسجيل الزوار
// ==========================================
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip_address !== '127.0.0.1' && $ip_address !== '::1' && !empty($ip_address)) {
    try {
        $check = $pdo->prepare("SELECT id FROM visitor_logs WHERE ip_address = ? AND DATE(visit_date) = CURDATE()");
        $check->execute([$ip_address]);
        
        if (!$check->fetch()) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $country = '🌐 زائر دولي';
            if (preg_match('/ar/i', $lang)) {
                $country = '🌍 دولة عربية';
            }
            
            $logStmt = $pdo->prepare("INSERT INTO visitor_logs (ip_address, user_agent, country, visit_date) VALUES (?, ?, ?, NOW())");
            $logStmt->execute([$ip_address, $user_agent, $country]);
        }
    } catch (\PDOException $e) {
        // حماية النظام في حال الخطأ
    }
}

// 0. جلب التخصصات والولايات ديناميكياً من قاعدة البيانات للفلاتر
$specialtiesList = [];
$governoratesList = [];
try {
    $specialtiesList = $pdo->query("SELECT * FROM specialties ORDER BY name ASC")->fetchAll();
    $governoratesList = $pdo->query("SELECT * FROM governorates ORDER BY name ASC")->fetchAll();
} catch (\PDOException $e) {
    // في حال عدم وجود الجداول أو حدوث خطأ
}

// 1. جلب الأطباء والعيادات المميزة (Premium) للسلايدر
$premiumDoctors = [];
try {
    $premiumStmt = $pdo->query("SELECT d.*, i.image_path FROM doctors d 
                                LEFT JOIN doctor_images i ON d.id = i.doctor_id AND i.is_profile = 1 
                                WHERE d.is_premium = 1 LIMIT 10");
    $premiumDoctors = $premiumStmt->fetchAll();
} catch (\PDOException $e) {
    $premiumDoctors = [];
}

// 2. بناء استعلام الفلترة والبحث للأطباء
$search = $_GET['search'] ?? '';
$specialty = $_GET['specialty'] ?? '';
$governorate = $_GET['governorate'] ?? '';
$degree = $_GET['degree'] ?? '';

$sql = "SELECT d.*, i.image_path FROM doctors d 
        LEFT JOIN doctor_images i ON d.id = i.doctor_id AND i.is_profile = 1 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (d.name LIKE :search OR d.specialty LIKE :search)";
    $params['search'] = "%$search%";
}
if (!empty($specialty)) {
    $sql .= " AND d.specialty = :specialty";
    $params['specialty'] = $specialty;
}
if (!empty($governorate)) {
    $sql .= " AND d.governorate = :governorate";
    $params['governorate'] = $governorate;
}
if (!empty($degree)) {
    $sql .= " AND d.degree = :degree";
    $params['degree'] = $degree;
}

$sql .= " ORDER BY d.is_premium DESC, d.id DESC";

$doctors = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $doctors = $stmt->fetchAll();
} catch (\PDOException $e) {
    $doctors = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-SLPCQ53Q9N"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-SLPCQ53Q9N');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل أطباء السودان - منصة الحجز والتواصل الطبي الأولى</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <style>
        @theme {
            --font-sans: 'Tajawal', sans-serif;
        }
        body { 
            font-family: 'Tajawal', sans-serif; 
        }
        .swiper-pagination-bullet-active { background: #ffffff !important; width: 20px; border-radius: 4px; }
        .swiper-pagination-bullet { background: rgba(255, 255, 255, 0.6); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <!-- الهيدر الرئيسي بألوان طبية -->
    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-sky-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between py-2 md:py-0 md:h-20 gap-3 md:gap-0">
            
            <!-- قسم الشعار مع إزالة القيود الصلبة -->
            <div class="flex items-center justify-between w-full md:w-auto">
                <a href="index.php" class="flex items-center py-1 transition-transform hover:scale-102">
                    <img src="uploads/logo.png" class="h-14 md:h-16 w-auto object-contain" alt="دليل أطباء السودان">
                </a>
            </div>
            
            <!-- حقل البحث -->
            <form action="" method="GET" class="w-full max-w-md">
                <div class="relative">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث باسم الطبيب أو التخصص الطبي..." class="w-full bg-slate-100 text-slate-700 pr-4 pl-10 py-2.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 text-sm transition-all border border-transparent focus:bg-white">
                    <button type="submit" class="absolute left-3 top-3 text-slate-400 hover:text-sky-600">🩺</button>
                </div>
            </form>

        </div>
    </div>
</nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- سلايدر الأطباء والعيادات المميزة -->
        <?php if (!empty($premiumDoctors) && empty($search)): ?>
        <div class="mb-10">
            <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span>⭐</span> العيادات والأطباء الأكثر تميزاً
            </h2>
            
            <div class="swiper premiumSwiper rounded-2xl shadow-xl">
                <div class="swiper-wrapper">
                    
                    <?php foreach ($premiumDoctors as $pDoctor): ?>
                        <div class="swiper-slide relative overflow-hidden bg-gradient-to-r from-sky-600 to-teal-600 p-6 md:p-8 text-white">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-4 md:mb-0">
                                <div class="max-w-lg text-right">
                                    <span class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">عيادة موصى بها</span>
                                    <h3 class="text-2xl md:text-3xl font-bold mt-3 mb-2"><?= htmlspecialchars($pDoctor['name']) ?></h3>
                                    <p class="text-white/90 text-sm font-medium mb-1">👨‍⚕️ التخصص: <?= htmlspecialchars($pDoctor['specialty']) ?></p>
                                    <p class="text-white/80 text-sm leading-relaxed mb-6 line-clamp-2"><?= htmlspecialchars($pDoctor['bio'] ?? $pDoctor['description'] ?? '') ?></p>
                                    
                                    <?php 
                                        $clean_wa = preg_replace('/[^0-9]/', '', $pDoctor['phone'] ?? $pDoctor['whatsapp'] ?? '');
                                        $wa_msg = urlencode("وجدت عيادتكم في الدليل وأرغب في الاستفسار/الحجز");
                                    ?>
                                    <div class="flex items-center gap-3">
                                        <a href="details.php?id=<?= $pDoctor['id'] ?>" class="inline-flex items-center gap-2 bg-white text-sky-700 px-5 py-2.5 rounded-xl font-bold text-sm shadow-md hover:bg-slate-100 transition-all">👉 عرض ملف الطبيب</a>
                                        <?php if(!empty($clean_wa)): ?>
                                            <a href="https://wa.me/<?= $clean_wa ?>?text=<?= $wa_msg ?>" target="_blank" class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-md transition-all">💬 حجز واتساب</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="w-full md:w-72 h-44 rounded-xl overflow-hidden shadow-lg border-2 border-white/20 shrink-0 bg-white">
                                    <img src="<?= htmlspecialchars($pDoctor['image_path'] ?? 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=500') ?>" class="w-full h-full object-cover" alt="Doctor Profile">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
                <div class="swiper-pagination !bottom-4"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- فلاتر البحث المتقدمة -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-8">
            <h3 class="text-sm font-bold text-sky-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                <span>🔍</span> فلترة البحث عن عيادة أو طبيب
            </h3>
            <form action="" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                
                <!-- فلتر التخصصات ديناميكي من جدول specialties -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">التخصص الطبي</label>
                    <select name="specialty" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white text-slate-700 transition-all">
                        <option value="">كل التخصصات</option>
                        <?php foreach($specialtiesList as $sp): ?>
                            <option value="<?= htmlspecialchars($sp['name']) ?>" <?= $specialty === $sp['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- فلتر الولايات ديناميكي من جدول governorates -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">المدينة / الولاية</label>
                    <select name="governorate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white text-slate-700 transition-all">
                        <option value="">كل المدن والولايات</option>
                        <?php foreach($governoratesList as $gov): ?>
                            <option value="<?= htmlspecialchars($gov['name']) ?>" <?= $governorate === $gov['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gov['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">الدرجة العلمية</label>
                    <select name="degree" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white text-slate-700 transition-all">
                        <option value="">الجميع</option>
                        <option value="استشاري" <?= $degree == 'استشاري' ? 'selected' : '' ?>>استشاري</option>
                        <option value="أخصائي" <?= $degree == 'أخصائي' ? 'selected' : '' ?>>أخصائي</option>
                        <option value="طبيب عام" <?= $degree == 'طبيب عام' ? 'selected' : '' ?>>طبيب عام</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-sky-600 text-white font-medium text-sm py-2 rounded-xl hover:bg-sky-700 shadow-md shadow-sky-100 transition-all cursor-pointer">بحث</button>
                    <?php if(!empty($search) || !empty($specialty) || !empty($governorate) || !empty($degree)): ?>
                        <a href="index.php" class="bg-slate-100 text-slate-600 py-2 px-3 rounded-xl hover:bg-slate-200 text-center text-sm transition-all" title="إلغاء الفلتر">🔄</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- قائمة الأطباء المتاحة -->
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-slate-900 mb-2">النتائج المتاحة (<?= count($doctors) ?> طبيب / عيادة)</h3>
            
            <?php if (empty($doctors)): ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-slate-100 shadow-sm">
                    <span class="text-4xl">🩺</span>
                    <p class="text-slate-500 mt-3 font-medium">عذراً، لم نجد أي أطباء أو عيادات تطابق خيارات الفلترة الحالية. جرب تغيير الفلاتر.</p>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doc): ?>
                    <div class="bg-white rounded-2xl p-5 border <?= $doc['is_premium'] ? 'border-amber-300 bg-amber-50/10' : 'border-slate-100' ?> shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-start md:items-center justify-between gap-5 relative group">
                        
                        <div class="flex flex-col sm:flex-row items-start gap-4 w-full md:max-w-4xl">
                            <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 shadow-sm mx-auto sm:mx-0">
                                <img src="<?= htmlspecialchars($doc['image_path'] ?? 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=500') ?>" class="w-full h-full object-cover" alt="Doctor Profile">
                            </div>
                            
                            <div class="space-y-1 w-full text-center sm:text-right">
                                <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                                    <h4 class="text-lg font-bold text-slate-900 group-hover:text-sky-600 transition-all"><?= htmlspecialchars($doc['name']) ?></h4>
                                    <?php if ($doc['is_premium']): ?>
                                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">⭐ موصى به</span>
                                    <?php endif; ?>
                                    <span class="bg-sky-50 text-sky-700 border border-sky-100 text-[11px] px-2 py-0.5 rounded-md font-bold">
                                        🏥 <?= htmlspecialchars($doc['specialty']) ?>
                                    </span>
                                </div>
                                
                                <p class="text-slate-500 text-xs sm:text-sm leading-relaxed line-clamp-2 sm:pl-4">
                                    <?= htmlspecialchars($doc['bio'] ?? $doc['description'] ?? '') ?>
                                </p>
                                
                                <div class="flex items-center justify-center sm:justify-start gap-4 text-xs text-slate-500 font-medium pt-1 flex-wrap">
                                    <span class="text-amber-500 font-bold flex items-center gap-0.5">
                                        ★ <?= number_format($doc['rating'] ?? 5.0, 1) ?>
                                    </span>
                                    <span>📍 <?= htmlspecialchars(($doc['governorate'] ?? '') . ' - ' . ($doc['city'] ?? '')) ?></span>
                                    <span class="text-teal-700 font-semibold">💰 الكشفية: <?= floatval($doc['fees']) > 0 ? number_format($doc['fees']) . ' ج.س' : 'غير محدد' ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- الأزرار والتواصل -->
                        <div class="w-full md:w-auto flex flex-wrap sm:flex-nowrap justify-center md:justify-end shrink-0 gap-2 pt-2 md:pt-0 border-t border-slate-100 md:border-t-0">
                            <?php 
                                $clean_doc_wa = preg_replace('/[^0-9]/', '', $doc['phone'] ?? $doc['whatsapp'] ?? '');
                                $wa_doc_msg = urlencode("وجدت عيادتكم في الدليل وأرغب في الاستفسار/الحجز");
                            ?>
                            <?php if(!empty($clean_doc_wa)): ?>
                                <a href="https://wa.me/<?= $clean_doc_wa ?>?text=<?= $wa_doc_msg ?>" target="_blank" class="inline-flex items-center justify-center gap-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-xl px-4 py-2 font-bold text-xs shadow-sm transition-all">
                                    <span>💬 واتساب</span>
                                </a>
                            <?php endif; ?>

                            <a href="details.php?id=<?= $doc['id'] ?>" class="inline-flex items-center justify-center gap-1.5 bg-sky-50 hover:bg-sky-600 text-sky-700 hover:text-white border border-sky-200 rounded-xl px-4 py-2 font-bold text-xs shadow-sm transition-all">
                                <span>التفاصيل بالحجم الكامل</span>
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <footer class="bg-white border-t border-slate-100 text-center py-6 mt-12 text-xs text-slate-400 font-medium">
        &copy; <?= date('Y') ?> دليل أطباء السودان. جميع الحقوق محفوظة.
    </footer>

    <script>
        const swiper = new Swiper('.premiumSwiper', {
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
        });
    </script>
</body>
</html>