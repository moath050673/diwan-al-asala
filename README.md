# متجر ديوان الأصالة — نسخة Laravel

نفس متجر ديوان الأصالة (عطور | بخور | زباد)، معاد بناؤه بالكامل بإطار **PHP Laravel** بدل Node.js،
بنفس المزايا: واجهة عربية RTL فاخرة، سلة، إتمام طلب (COD/جيب/كريمي)، تكامل واتساب، ولوحة تحكم كاملة.

---

## 1. المتطلبات

- PHP 8.2 أو أحدث
- Composer
- MySQL 8 (أو MariaDB حديث)
- امتدادات PHP المعتادة مع Laravel (pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd)

---

## 2. التثبيت

```bash
composer install
```

هذا الأمر يحمّل Laravel Framework و Sanctum وبقية الحزم المذكورة في `composer.json` وينشئ مجلد `vendor/`.

---

## 3. إعداد ملف `.env`

```bash
cp .env.example .env
php artisan key:generate
```

ثم عبّئ في `.env`:

| المتغير | الوصف |
|---|---|
| `DB_*` | بيانات الاتصال بقاعدة بيانات MySQL |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | تُستخدم مرة واحدة فقط لإنشاء حساب المدير الأول |
| `WHATSAPP_NUMBER` | رقم واتساب المتجر (صيغة دولية بدون + أو 00) |
| `JIB_ACCOUNT_NAME` / `JIB_ACCOUNT_NUMBER` | بيانات حساب جيب |
| `KAREEMI_ACCOUNT_NAME` / `KAREEMI_ACCOUNT_NUMBER` | بيانات حساب كريمي |
| `FACEBOOK_URL` / `INSTAGRAM_URL` | روابط صفحات المتجر |
| `STORE_NAME` / `STORE_CURRENCY` / `SHIPPING_COST_SANAA` | اسم المتجر، العملة، تكلفة التوصيل الافتراضية |

**لا ترفع ملف `.env` إلى Git أبدًا.**

---

## 4. إعداد قاعدة البيانات

أنشئ قاعدة بيانات فارغة باسم `diwan_alasala` (أو أي اسم تضعه في `DB_DATABASE`)، ثم:

```bash
php artisan migrate
```

ينشئ هذا كل الجداول (users, categories, products, orders...) دفعة واحدة، بدل ملف `schema.sql` المنفصل في نسخة Node.js.

---

## 5. تعبئة بيانات تجريبية (اختياري لكن موصى به)

```bash
php artisan db:seed
```

يعبّئ إعدادات المتجر الافتراضية + تصنيفات ومنتجات تجريبية (Placeholder يمكن حذفها لاحقًا من لوحة التحكم).

---

## 6. إنشاء حساب المدير الأول

بعد تعبئة `ADMIN_EMAIL` و `ADMIN_PASSWORD` في `.env`:

```bash
php artisan db:seed --class=AdminUserSeeder
```

- **يُجبر على تغيير كلمة المرور فورًا** عند أول تسجيل دخول للوحة التحكم.
- احذف/غيّر `ADMIN_PASSWORD` في `.env` بعد الإنشاء.

---

## 7. ربط مجلد الرفع (صور المنتجات)

```bash
php artisan storage:link
```

هذا الأمر (المكافئ الرسمي في Laravel) يجعل صور المنتجات المرفوعة من لوحة التحكم متاحة عبر الرابط `/storage/...`.

---

## 8. تشغيل المشروع — تمامًا مثل `php artisan serve` اللي تعرفه

```bash
php artisan serve
```

الموقع كامل (الواجهة الأمامية + لوحة التحكم + API) يعمل من خادم واحد فقط على:

```text
http://localhost:8000
```

- الواجهة الأمامية: `http://localhost:8000/`
- لوحة التحكم: `http://localhost:8000/admin`
- الـ API: `http://localhost:8000/api/...`

**ملاحظة مهمة:** بخلاف نسخة Node.js (سيرفرين منفصلين)، نسخة Laravel هذه سيرفر واحد فقط — بالضبط مثل أي مشروع Laravel اعتدت عليه.

---

## 9. روابط API الرئيسية

```text
POST   /api/auth/login
GET    /api/auth/me                 (Sanctum)
POST   /api/auth/change-password    (Sanctum)

GET    /api/products
GET    /api/products/{id}
POST   /api/products                (admin/staff)
PUT    /api/products/{id}           (admin/staff)
DELETE /api/products/{id}           (admin)

GET    /api/categories
POST   /api/categories              (admin)

POST   /api/orders                  (عام - Guest Checkout)
GET    /api/orders                  (admin/staff)
PUT    /api/orders/{id}/status      (admin/staff)

GET    /api/payments/settings       (عام)
PUT    /api/payments/{id}/status    (admin/staff)

GET    /api/settings                (عام)
PUT    /api/settings                (admin)

POST   /api/contact
GET    /api/dashboard/stats         (admin/staff)
```

المصادقة تتم عبر **Laravel Sanctum** (Personal Access Tokens) — نفس أسلوب `Authorization: Bearer <token>`
المستخدم في نسخة Node.js، فالواجهة الأمامية (JS) تعمل بدون أي تعديل يُذكر.

---

## 10. الفروقات الجوهرية عن نسخة Node.js

| الجانب | Node.js | Laravel |
|---|---|---|
| اللغة | JavaScript | PHP |
| الخادم | Express (منفذ منفصل 4000) | خادم Laravel نفسه (منفذ 8000) |
| ORM | استعلامات SQL مباشرة (mysql2) | Eloquent ORM |
| المصادقة | JWT يدوي | Laravel Sanctum |
| الهجرات | `database/schema.sql` يدوي | `php artisan migrate` |
| البيانات التجريبية | `database/seed.sql` | `php artisan db:seed` |
| الواجهة | ملفات HTML ثابتة | Blade Views (نفس HTML تقريبًا، بمحرك Laravel) |
| رفع الصور | `multer` + مجلد `uploads/` | `Storage` + `php artisan storage:link` |

**الشكل والتصميم متطابقان 100%** — نفس ملفات CSS، ونفس منطق JavaScript في الواجهة (السلة، الفلاتر، واتساب)،
فقط الروابط الداخلية أصبحت أنظف (`/products` بدل `products.html`، `/product/5` بدل `product.html?id=5`).

---

## 11. اختبار مسار الطلب الكامل

نفس السيناريو تمامًا كنسخة Node.js:

1. تصفح `/products` → أضف منتج للسلة.
2. من `/cart` اضغط "إتمام الطلب".
3. عبّئ البيانات واختر طريقة الدفع.
4. `OrderService` (في `app/Services/OrderService.php`) يعيد حساب الأسعار والمخزون من قاعدة البيانات داخل Transaction، ويمنع تكرار خصم المخزون.
5. تظهر `/order-success` برقم الطلب وزر واتساب معبأ تلقائيًا.
6. من `/admin/orders` يراجع المدير الطلب ويغيّر حالته عبر السلسلة الكاملة.
7. لطرق الدفع اليدوية (جيب/كريمي)، يراجع المدير رقم العملية من `/admin/payments` → `/admin/orders`.

---

## 12. الأمان — ما تم تطبيقه

- تشفير كلمات المرور تلقائيًا عبر `casts(): ['password' => 'hashed']` في نموذج `User`.
- مصادقة Sanctum + Middleware مخصص `EnsureRole` للتفويض حسب الدور.
- Laravel Validation على كل مدخلات الـ API (`$request->validate()`).
- حماية CSRF/XSS مدمجة افتراضيًا في Laravel، وEloquent يحمي تلقائيًا من SQL Injection.
- إعادة حساب الأسعار والمخزون دائمًا من الخادم عبر `OrderService`، مع `lockForUpdate()` لمنع تعارض المخزون عند الطلبات المتزامنة.
- التحقق من نوع وحجم صور المنتجات المرفوعة (`image|mimes:jpg,jpeg,png,webp|max:5120`).
- لا توجد أي أسرار داخل الكود — كل شيء عبر `.env` أو جدول `settings`.

---

## 13. قبل النشر فعليًا — قائمة تحقق

- [ ] `APP_ENV=production` و `APP_DEBUG=false` في `.env` على السيرفر النهائي.
- [ ] `php artisan config:cache` و `php artisan route:cache` لتحسين الأداء.
- [ ] استبدال جميع قيم `.env` بقيم حقيقية (قاعدة بيانات، رقم واتساب، جيب، كريمي).
- [ ] `php artisan db:seed --class=AdminUserSeeder` بكلمة مرور قوية، ثم تغييرها فور أول دخول.
- [ ] حذف/استبدال بيانات `ProductSeeder` التجريبية بمنتجات حقيقية من لوحة التحكم.
- [ ] `php artisan storage:link` على السيرفر النهائي لتفعيل رفع الصور.
- [ ] تفعيل HTTPS، وتحديث `APP_URL` في `.env`.
- [ ] نسخ احتياطي دوري لقاعدة البيانات.
