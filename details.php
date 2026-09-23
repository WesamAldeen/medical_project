<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// جلب معرف الطبيب من الرابط
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

// جلب تفاصيل الطبيب والصورة الشخصية
$doctor = null;
try {
    $stmt = $pdo->prepare("SELECT d.*, i.image_path FROM doctors d 
                           LEFT JOIN doctor_images i ON d.id = i.doctor_id AND i.is_profile = 1 
                           WHERE d.id = ?");
    $stmt->execute([$id]);
    $doctor = $stmt->fetch();
} catch (\PDOException $e) {
    $doctor = null;
}

if (!$doctor) {
    header("Location: index.php");
    exit;
}

// تجهيز رقم الهاتف للواتساب والاتصال
$rawPhone = $doctor['phone'] ?? $doctor['whatsapp'] ?? '';
$cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
$waMsg = urlencode("مرحباً دكتور، وجدت بياناتكم في دليل أطباء السودان وأرغب في الاستفسار/الحجز.");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($doctor['name']) ?> - دليل أطباء السودان</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        @theme { --font-sans: 'Tajawal', sans-serif; }
        body { font-family: 'Tajawal', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <!-- الهيدر -->
    <nav class="bg-white shadow-sm border-b border-sky-100 py-4 px-6 mb-8">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="index.php" class="text-xl font-bold text-sky-700 flex items-center gap-2">
                🩺 دليل أطباء السودان
            </a>
            <a href="index.php" class="text-xs font-bold text-slate-600 bg-slate-100 px-4 py-2 rounded-xl hover:bg-slate-200 transition-all">
                ← العودة للرئيسية
            </a>
        </div>
    </nav>

    <!-- المحتوى الرئيسي -->
    <main class="max-w-4xl mx-auto px-4 pb-12">
        <div class="bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 space-y-8">
            
            <!-- الهيدر الداخلي للطبيب -->
            <div class="flex flex-col md:flex-row items-center gap-6 border-b border-slate-100 pb-8 text-center md:text-right">
                <div class="w-32 h-32 rounded-2xl overflow-hidden bg-slate-100 border-2 border-sky-100 shrink-0 shadow-md">
                    <img src="<?= htmlspecialchars($doctor['image_path'] ?? 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=500') ?>" class="w-full h-full object-cover" alt="صورة الطبيب">
                </div>
                
                <div class="space-y-2 flex-1">
                    <div class="flex items-center justify-center md:justify-start gap-2 flex-wrap">
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900"><?= htmlspecialchars($doctor['name']) ?></h1>
                        <?php if (!empty($doctor['is_premium'])): ?>
                            <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-full">⭐ موصى به</span>
                        <?php endif; ?>
                    </div>

                    <p class="text-sky-700 font-bold text-base">🏥 <?= htmlspecialchars($doctor['specialty']) ?></p>
                    
                    <?php if (!empty($doctor['degree'])): ?>
                        <p class="text-slate-500 text-sm">🎓 <?= htmlspecialchars($doctor['degree']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- شبكة التفاصيل -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-xs text-slate-400 block mb-1">الموقع والتواجد</span>
                    <p class="font-bold text-slate-700 text-sm">📍 <?= htmlspecialchars(($doctor['governorate'] ?? '') . ' - ' . ($doctor['city'] ?? '')) ?></p>
                </div>

                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <span class="text-xs text-slate-400 block mb-1">قيمة المعاينة / الكشفية</span>
                    <p class="font-bold text-teal-700 text-sm">💰 <?= floatval($doctor['fees'] ?? 0) > 0 ? number_format($doctor['fees']) . ' جنيه سوداني' : 'غير محددة' ?></p>
                </div>
            </div>

            <!-- النبذة والوصف -->
            <?php if (!empty($doctor['bio']) || !empty($doctor['description'])): ?>
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-slate-900">📝 نبذة عن الطبيب والخدمات:</h3>
                    <p class="text-slate-600 text-sm leading-relaxed bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                        <?= nl2br(htmlspecialchars($doctor['bio'] ?? $doctor['description'] ?? '')) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- أزرار التواصل -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                <?php if (!empty($cleanPhone)): ?>
                    <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waMsg ?>" target="_blank" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-center font-bold py-3.5 px-4 rounded-xl shadow-md transition-all text-sm flex items-center justify-center gap-2">
                        💬 التواصل عبر الواتساب
                    </a>
                    <a href="tel:<?= $cleanPhone ?>" class="flex-1 bg-sky-600 hover:bg-sky-700 text-white text-center font-bold py-3.5 px-4 rounded-xl shadow-md transition-all text-sm flex items-center justify-center gap-2">
                        📞 اتصال هاتفياً
                    </a>
                <?php else: ?>
                    <div class="w-full text-center text-slate-400 text-sm py-2">لا تتوفر أرقام تواصل مسجلة لهذا الطبيب حالياً.</div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>