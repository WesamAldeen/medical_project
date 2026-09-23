<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

$error = '';
$doctorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($doctorId <= 0) {
    header("Location: wesamadmin.php");
    exit;
}

// 1. جلب بيانات الطبيب الحالي
$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header("Location: wesamadmin.php");
    exit;
}

// 2. جلب التخصصات والولايات من قاعدة البيانات
$specialties = $pdo->query("SELECT * FROM specialties ORDER BY name ASC")->fetchAll();
$governorates = $pdo->query("SELECT * FROM governorates ORDER BY name ASC")->fetchAll();

// 3. معالجة التحديث
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $specialty   = trim($_POST['specialty'] ?? '');
    $degree      = trim($_POST['degree'] ?? '');
    $governorate = trim($_POST['governorate'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $fees        = floatval($_POST['fees'] ?? 0);
    $is_premium  = isset($_POST['is_premium']) ? 1 : 0;
    $phone       = trim($_POST['phone'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name) || empty($specialty) || empty($governorate)) {
        $error = 'يرجى ملء جميع الحقول الأساسية (الاسم، التخصص، والولاية).';
    } else {
        try {
            $updateStmt = $pdo->prepare("UPDATE doctors SET name = ?, specialty = ?, degree = ?, governorate = ?, city = ?, fees = ?, is_premium = ?, phone = ?, description = ? WHERE id = ?");
            $updateStmt->execute([$name, $specialty, $degree, $governorate, $city, $fees, $is_premium, $phone, $description, $doctorId]);

            if (!empty($_FILES['image']['name'])) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newFileName = 'doctor_' . $doctorId . '_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    // إلغاء تحديد الصور القديمة كبروفايل
                    $resetProfile = $pdo->prepare("UPDATE doctor_images SET is_profile = 0 WHERE doctor_id = ?");
                    $resetProfile->execute([$doctorId]);

                    // إضافة الصورة الجديدة
                    $imgInsert = $pdo->prepare("INSERT INTO doctor_images (doctor_id, image_path, is_profile) VALUES (?, ?, 1)");
                    $imgInsert->execute([$doctorId, $targetFile]);
                }
            }

            header("Location: wesamadmin.php?success=" . urlencode("تم تحديث بيانات الطبيب بنجاح"));
            exit;
        } catch (\PDOException $e) {
            $error = '⚠️ خطأ أثناء التحديث: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات الطبيب - لوحة التحكم</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style> 
        @theme { --font-sans: 'Tajawal', sans-serif; }
        body { font-family: 'Tajawal', sans-serif; } 
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="bg-white shadow-sm border-b border-slate-100 py-4 px-6 mb-8">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900">✏️ تعديل بيانات الطبيب</h1>
            <a href="wesamadmin.php" class="text-xs font-bold text-slate-600 bg-slate-100 px-4 py-2 rounded-xl hover:bg-slate-200 transition-all">← العودة للوحة التحكم</a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 pb-12">
        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 text-sm p-4 rounded-xl mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-slate-100 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- اسم الطبيب -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">اسم الطبيب أو المركز الطبي *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>" required class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- التخصص الطبي (قائمة منسدلة مع اختيار التخصص الحالي تلقائياً) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">التخصص الطبي *</label>
                    <select name="specialty" required class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="">-- اختر التخصص الطبي --</option>
                        <?php foreach($specialties as $sp): ?>
                            <option value="<?= htmlspecialchars($sp['name']) ?>" <?= ($sp['name'] === $doctor['specialty']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- الدرجة العلمية -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">الدرجة العلمية / المسمى الوظيفي</label>
                    <input type="text" name="degree" value="<?= htmlspecialchars($doctor['degree'] ?? '') ?>" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- الولاية / المحافظة -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">الولاية / المحافظة *</label>
                    <select name="governorate" required class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="">-- اختر الولاية --</option>
                        <?php foreach($governorates as $gov): ?>
                            <option value="<?= htmlspecialchars($gov['name']) ?>" <?= ($gov['name'] === $doctor['governorate']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gov['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- المدينة / الحي -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">المدينة / الحي</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($doctor['city'] ?? '') ?>" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- قيمة الكشف -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">قيمة الكشف / المعاينة</label>
                    <input type="number" name="fees" step="0.01" value="<?= htmlspecialchars($doctor['fees'] ?? 0) ?>" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- رقم الهاتف -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">رقم التواصل / الواتساب</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($doctor['phone'] ?? '') ?>" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- صورة البروفايل الجديدة -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">تغيير الصورة الشخصية (اختياري)</label>
                    <input type="file" name="image" accept="image/*" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50">
                </div>
            </div>

            <!-- نبذة عن الطبيب -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">نبذة مختصرة أو الخدمات المقدمة</label>
                <textarea name="description" rows="3" class="w-full p-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"><?= htmlspecialchars($doctor['description'] ?? '') ?></textarea>
            </div>

            <!-- خيار التميز -->
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_premium" id="is_premium" value="1" <?= $doctor['is_premium'] ? 'checked' : '' ?> class="w-4 h-4 text-sky-600 rounded">
                <label for="is_premium" class="text-sm font-bold text-slate-700 cursor-pointer">⭐ إدراج هذا الطبيب ضمن السلايدر المتميز</label>
            </div>

            <button type="submit" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-3.5 rounded-xl transition-all text-sm shadow-sm">
                تحديث البيانات 💾
            </button>
        </form>
    </main>
</body>
</html>