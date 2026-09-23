<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// ==========================================
// أولاً: معالجة العمليات (حذف / تعديل التميز)
// ==========================================

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $delStmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
        $delStmt->execute([$delete_id]);
        header("Location: wesamadmin.php?success=تم حذف الطبيب/العيادة بنجاح");
        exit;
    } catch (\PDOException $e) {
        header("Location: wesamadmin.php?error=تعذر الحذف: " . $e->getMessage());
        exit;
    }
}

if (isset($_GET['toggle_premium_id']) && isset($_GET['current_status'])) {
    $doctor_id = intval($_GET['toggle_premium_id']);
    $new_status = intval($_GET['current_status']) == 1 ? 0 : 1;
    
    try {
        $updateStmt = $pdo->prepare("UPDATE doctors SET is_premium = ? WHERE id = ?");
        $updateStmt->execute([$new_status, $doctor_id]);
        header("Location: wesamadmin.php?success=تم تحديث حالة الطبيب المتميز بنجاح");
        exit;
    } catch (\PDOException $e) {
        header("Location: wesamadmin.php?error=تعذر التحديث: " . $e->getMessage());
        exit;
    }
}

// ==========================================
// ثانياً: جلب الإحصائيات والزوار من قاعدة البيانات
// ==========================================
$total_doctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$premium_count = $pdo->query("SELECT COUNT(*) FROM doctors WHERE is_premium = 1")->fetchColumn();

$visitor_logs = [];
$unique_visitors = 0;
try {
    $unique_visitors = $pdo->query("SELECT COUNT(*) FROM visitor_logs")->fetchColumn();
    $logStmt = $pdo->query("SELECT * FROM visitor_logs ORDER BY id DESC LIMIT 10");
    $visitor_logs = $logStmt->fetchAll();
} catch (\PDOException $e) {
    $unique_visitors = 0;
}

// دالة تحليل نظام التشغيل والمتصفح
function parseAgent($ua) {
    $platform = "💻 كمبيوتر / مجهول";
    $browser  = "متصفح افتراضي";
    if (empty($ua)) return ['platform' => $platform, 'browser' => $browser];

    if (preg_match('/iphone/i', $ua)) { $platform = "📱 آيفون (iPhone)"; }
    elseif (preg_match('/android/i', $ua)) { $platform = "📱 أندرويد (Android)"; }
    elseif (preg_match('/ipad/i', $ua)) { $platform = "📟 آيباد (iPad)"; }
    elseif (preg_match('/windows/i', $ua)) { $platform = "💻 ويندوز (Windows)"; }
    elseif (preg_match('/macintosh/i', $ua)) { $platform = "💻 ماك (Mac)"; }
    
    if (preg_match('/chrome/i', $ua)) { $browser = "Chrome"; }
    elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) { $browser = "Safari"; }
    elseif (preg_match('/firefox/i', $ua)) { $browser = "Firefox"; }
    elseif (preg_match('/edge/i', $ua)) { $browser = "Edge"; }
    return ['platform' => $platform, 'browser' => $browser];
}

// جلب قائمة الأطباء الحالية
$doctorsStmt = $pdo->query("SELECT d.*, i.image_path FROM doctors d LEFT JOIN doctor_images i ON d.id = i.doctor_id AND i.is_profile = 1 ORDER BY d.id DESC");
$allDoctors = $doctorsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <!-- Google tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-SLPCQ53Q9N"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-SLPCQ53Q9N');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم - إدارة دليل الأطباء</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style> 
        @theme { --font-sans: 'Tajawal', sans-serif; }
        body { font-family: 'Tajawal', sans-serif; } 
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white shadow-sm sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <span class="text-xl font-bold text-slate-900">لوحة الإدارة والدليل الطبي 🩺</span>
            <a href="index.php" target="_blank" class="text-xs font-bold text-sky-600 bg-sky-50 px-4 py-2 rounded-xl hover:bg-sky-100 transition-all">🔗 استعراض الدليل الحقيقي</a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm p-3 rounded-xl mb-6">✅ <?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm p-3 rounded-xl mb-6">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- كروت الإحصائيات الرقمية -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div><p class="text-xs text-slate-400 font-semibold mb-1">إجمالي الأطباء والعيادات</p><h3 class="text-2xl font-bold text-slate-900"><?= $total_doctors ?> طبيب/عيادة</h3></div>
                <span class="text-3xl bg-sky-50 p-3 rounded-xl">👨‍⚕️</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div><p class="text-xs text-slate-400 font-semibold mb-1">الأطباء المتميزون (السلايدر)</p><h3 class="text-2xl font-bold text-amber-600"><?= $premium_count ?> طبيب</h3></div>
                <span class="text-3xl bg-amber-50 p-3 rounded-xl">⭐</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div><p class="text-xs text-slate-400 font-semibold mb-1">إجمالي زيارات الدليل الفعلية</p><h3 class="text-2xl font-bold text-indigo-600"><?= $unique_visitors ?> زيارة</h3></div>
                <span class="text-3xl bg-indigo-50 p-3 rounded-xl">📊</span>
            </div>
        </div>

        <!-- جدول سجل الزوار -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-8">
            <h3 class="text-sm font-bold text-slate-700 mb-4">🖥️ سجل الزوار النشطين وتفاصيل الأجهزة الحية</h3>
            
            <div class="max-h-[400px] overflow-y-auto overflow-x-auto border border-slate-50 rounded-xl">
                <table class="w-full text-right border-collapse text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold sticky top-0 z-10 shadow-xs">
                        <tr>
                            <th class="p-3 bg-slate-50">رقم الزيارة</th>
                            <th class="p-3 bg-slate-50">عنوان الـ IP</th>
                            <th class="p-3 bg-slate-50">نوع الجهاز والنظام</th>
                            <th class="p-3 bg-slate-50">المتصفح</th>
                            <th class="p-3 bg-slate-50">المنطقة والبلد</th>
                            <th class="p-3 bg-slate-50">تاريخ ووقت الزيارة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600 bg-white">
                        <?php if(empty($visitor_logs)): ?>
                            <tr><td colspan="6" class="p-4 text-center text-slate-400">لم يتم رصد زيارات حية بعد.</td></tr>
                        <?php else: ?>
                            <?php foreach($visitor_logs as $log): 
                                $agent = parseAgent($log['user_agent'] ?? '');
                            ?>
                                <tr class="hover:bg-slate-50/50 transition-all">
                                    <td class="p-3 font-mono text-slate-400">#<?= $log['id'] ?></td>
                                    <td class="p-3 font-mono font-bold text-sky-600"><?= htmlspecialchars($log['ip_address'] ?? 'unknown') ?></td>
                                    <td class="p-3 font-bold text-slate-800"><?= $agent['platform'] ?></td>
                                    <td class="p-3"><span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md font-medium"><?= $agent['browser'] ?></span></td>
                                    <td class="p-3 text-emerald-600 font-bold"><?= htmlspecialchars($log['country'] ?? '🌍 جاري التحليل') ?></td>
                                    <td class="p-3 text-slate-400"><?= htmlspecialchars($log['visit_date'] ?? 'غير مسجل') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- إدارة الأطباء والعيادات -->
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-900">🗂️ قائمة الأطباء الحالية وإدارتهم</h3>
            <a href="add_doctor.php" class="bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm flex items-center gap-1 transition-all">
                <span>إضافة طبيب</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase">
                            <th class="p-4">الطبيب/العيادة</th>
                            <th class="p-4">التخصص والدرجة</th>
                            <th class="p-4">الموقع الجغرافي</th>
                            <th class="p-4">رسوم الكشف</th>
                            <th class="p-4">حالة الإعلان</th>
                            <th class="p-4 text-center">إجراءات التحكم</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($allDoctors)): ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400 font-medium">لا يوجد أطباء مضافون حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($allDoctors as $doc): ?>
                                <tr class="hover:bg-slate-50/80 transition-all">
                                    <td class="p-4 flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl overflow-hidden bg-slate-50 border shrink-0">
                                            <img src="<?= htmlspecialchars($doc['image_path'] ?? 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=100') ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div><h4 class="font-bold text-slate-900"><?= htmlspecialchars($doc['name']) ?></h4></div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($doc['specialty'] ?? 'عام') ?></p>
                                        <p class="text-[11px] text-slate-400"><?= htmlspecialchars($doc['degree'] ?? 'أخصائي') ?></p>
                                    </td>
                                    <td class="p-4"><p class="text-xs text-slate-700">📍 <?= htmlspecialchars($doc['governorate'] . ' - ' . $doc['city']) ?></p></td>
                                    <td class="p-4 font-bold text-emerald-600"><?= number_format($doc['fees'], 0) ?> ج.س</td>
                                    <td class="p-4">
                                        <?php if($doc['is_premium']): ?>
                                            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-xs px-2 py-0.5 rounded-md font-bold">⭐ مميز بالسلايدر</span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs">عادي</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="inline-flex gap-2">
                                            <a href="edit-doctor.php?id=<?= $doc['id'] ?>" class="text-xs font-bold px-3 py-1.5 rounded-lg border text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all">📝 تعديل</a>
                                            <a href="wesamadmin.php?toggle_premium_id=<?= $doc['id'] ?>&current_status=<?= $doc['is_premium'] ?>" class="text-xs font-bold px-3 py-1.5 rounded-lg border text-slate-600 bg-slate-50 hover:bg-slate-100 transition-all">⭐ تبديل الحالة</a>
                                            <a href="wesamadmin.php?delete_id=<?= $doc['id'] ?>" onclick="return confirm('هل أنت تأكد من الحذف النهائي؟');" class="text-xs font-bold px-3 py-1.5 rounded-lg border text-rose-600 bg-rose-50 hover:bg-rose-100 transition-all">🗑️ حذف</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>