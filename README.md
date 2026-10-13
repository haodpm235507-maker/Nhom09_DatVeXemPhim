# CineBook v2.0 – Hệ thống Đặt Vé Xem Phim Online

> Hệ thống phục vụ đồ án môn **Kiểm thử và Đảm bảo chất lượng phần mềm**.  
> Được thiết kế đủ nghiệp vụ, đủ tình huống lỗi và đủ luồng phức tạp để nhóm sinh viên thực hiện đầy đủ quy trình kiểm thử.

---

## Mục lục

1. [Giới thiệu](#1-giới-thiệu)
2. [Công nghệ sử dụng](#2-công-nghệ-sử-dụng)
3. [Yêu cầu hệ thống](#3-yêu-cầu-hệ-thống)
4. [Cài đặt chi tiết](#4-cài-đặt-chi-tiết)
5. [Cấu trúc thư mục](#5-cấu-trúc-thư-mục)
6. [Tài khoản & Dữ liệu mẫu](#6-tài-khoản--dữ-liệu-mẫu)
7. [Chức năng hệ thống](#7-chức-năng-hệ-thống)
8. [Luồng nghiệp vụ chính](#8-luồng-nghiệp-vụ-chính)
9. [Tính năng phục vụ kiểm thử](#9-tính-năng-phục-vụ-kiểm-thử)
10. [API nội bộ](#10-api-nội-bộ)
11. [Database](#11-database)
12. [Bảo mật](#12-bảo-mật)
13. [Hướng dẫn kiểm thử nhanh](#13-hướng-dẫn-kiểm-thử-nhanh)
14. [Reset dữ liệu test](#14-reset-dữ-liệu-test)
15. [Xử lý lỗi thường gặp](#15-xử-lý-lỗi-thường-gặp)
16. [Ghi chú phát triển](#16-ghi-chú-phát-triển)

---

## 1. Giới thiệu

**CineBook** là website đặt vé xem phim online hoàn chỉnh, viết bằng PHP thuần + MySQL, giao diện Bootstrap 5, toàn bộ tiếng Việt.

Hệ thống được thiết kế **không quá đơn giản**, có đủ:

- Phân quyền Admin / Khách hàng
- Luồng đặt vé nhiều bước (phim → suất → ghế → thanh toán)
- Xử lý xung đột ghế (hold + transaction)
- Timeout thanh toán tự động
- Mã giảm giá (hợp lệ / hết hạn / hết lượt)
- Hủy vé theo điều kiện thời gian
- Nhật ký hệ thống
- Validation frontend + backend rõ ràng

Mục tiêu: nhóm có thể viết **50–100 Test Case**, áp dụng EP, BVA, Decision Table, kiểm thử xung đột, automation, performance…

---

## 2. Công nghệ sử dụng

| Thành phần     | Công nghệ                          |
|----------------|------------------------------------|
| Frontend       | HTML5, CSS3, JavaScript, Bootstrap 5.3 |
| Backend        | PHP 8+ (thuần, không framework)    |
| Database       | MySQL 5.7+ / MariaDB               |
| Web server     | Apache (XAMPP / Laragon / Vertigo) |
| Bảo mật        | PDO Prepared Statement, password_hash, Session, CSRF Token |
| AJAX           | Fetch API (vanilla JS)             |

**Không dùng:** Laravel, React, Vue, jQuery (chỉ Bootstrap JS).

---

## 3. Yêu cầu hệ thống

- PHP >= 7.4 (khuyến nghị 8.0+)
- MySQL / MariaDB
- Extension PHP: `pdo_mysql`, `mbstring`, `session`
- Trình duyệt hiện đại (Chrome, Firefox, Edge)

---

## 4. Cài đặt chi tiết

### Bước 1: Copy source

Giải nén file zip và copy thư mục `dat-ve-xem-phim` vào:

- **XAMPP:** `C:\xampp\htdocs\`
- **Laragon:** `C:\laragon\www\`
- **Linux:** `/var/www/html/`

### Bước 2: Tạo database

1. Mở phpMyAdmin: http://localhost/phpmyadmin  
2. Chọn tab **Import**  
3. Chọn file `database.sql` trong thư mục dự án  
4. Bấm **Go** / **Thực hiện**

Hoặc chạy bằng dòng lệnh:

```bash
mysql -u root -p < dat-ve-xem-phim/database.sql
```

### Bước 3: Cấu hình kết nối (nếu cần)

Mở file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dat_ve_xem_phim');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP mặc định để trống
define('DB_CHARSET', 'utf8mb4');
```

Nếu dùng Laragon hoặc MySQL có mật khẩu, sửa `DB_PASS` cho đúng.

### Bước 4: Truy cập hệ thống

| Trang              | URL                                              |
|--------------------|--------------------------------------------------|
| Trang chủ          | http://localhost/dat-ve-xem-phim/                |
| Đăng nhập          | http://localhost/dat-ve-xem-phim/auth/dang_nhap.php |
| Trang quản trị     | http://localhost/dat-ve-xem-phim/admin/          |

---

## 5. Cấu trúc thư mục

```
dat-ve-xem-phim/
│
├── config/
│   └── database.php          # Kết nối DB + helper session
│
├── includes/
│   ├── header.php            # Header chung + menu
│   ├── footer.php            # Footer + Bootstrap JS
│   └── functions.php         # Hàm tiện ích (CSRF, log, hold ghế, timeout…)
│
├── assets/
│   ├── css/style.css         # CSS tùy chỉnh (sơ đồ ghế, card…)
│   ├── js/script.js          # JS validation form
│   └── images/               # (trống – dùng placeholder)
│
├── auth/
│   ├── dang_ky.php           # Đăng ký tài khoản
│   ├── dang_nhap.php         # Đăng nhập
│   └── dang_xuat.php         # Đăng xuất
│
├── admin/
│   ├── index.php             # Dashboard thống kê
│   ├── sidebar.php           # Menu admin
│   ├── phim.php              # Danh sách phim
│   ├── them_phim.php         # Thêm phim
│   ├── sua_phim.php          # Sửa phim
│   ├── rap.php               # Quản lý rạp
│   ├── phong.php             # Quản lý phòng + tự tạo ghế
│   ├── suat_chieu.php        # Quản lý suất chiếu (có check xung đột)
│   ├── ma_giam_gia.php       # Quản lý mã giảm giá
│   ├── ve.php                # Quản lý vé / đơn hàng
│   ├── nguoi_dung.php        # Quản lý user (khóa/mở)
│   └── nhat_ky.php           # Xem nhật ký hệ thống
│
├── api/
│   ├── ghe_status.php        # API lấy trạng thái ghế (AJAX)
│   └── hold_ghe.php          # API giữ ghế tạm thời
│
├── index.php                 # Trang chủ
├── danh_sach_phim.php        # Danh sách + tìm kiếm + lọc
├── chi_tiet_phim.php         # Chi tiết phim + lịch chiếu
├── chon_ghe.php              # ★ Chọn ghế (hold + AJAX + countdown)
├── thanh_toan.php            # Thanh toán mô phỏng + timeout
├── ve_cua_toi.php            # Lịch sử vé của khách
├── huy_ve.php                # Hủy vé (điều kiện 30 phút)
├── reset_data.php            # Script reset dữ liệu test
├── database.sql              # Schema + dữ liệu mẫu
└── README.md                 # File này
```

---

## 6. Tài khoản & Dữ liệu mẫu

### Tài khoản đăng nhập

| Vai trò      | Tên đăng nhập | Mật khẩu | Ghi chú                |
|--------------|---------------|----------|------------------------|
| Admin        | `admin`       | `123456` | Toàn quyền quản trị    |
| Khách hàng   | `nguyenvana`  | `123456` | User thường            |
| Khách hàng   | `tranthib`    | `123456` | User thường            |
| Khách hàng   | `levanc`      | `123456` | User thường            |
| Khách hàng   | `phamthid`    | `123456` | User thường            |

> Mật khẩu đã được hash bằng `password_hash()` (PASSWORD_DEFAULT).

### Mã giảm giá mẫu

| Mã code   | Loại       | Giá trị | Trạng thái     | Mục đích test          |
|-----------|------------|---------|----------------|------------------------|
| `GIAM10`  | Phần trăm  | 10%     | Còn hạn        | Positive               |
| `GIAM20K` | Số tiền    | 20.000đ | Còn hạn        | Positive               |
| `VIP30`   | Phần trăm  | 30%     | Còn hạn        | Positive               |
| `GIAM50`  | Phần trăm  | 50%     | Còn hạn        | Positive               |
| `HETHAN`  | Phần trăm  | 15%     | **Hết hạn**    | Negative – hết hạn     |
| `TESTMAX` | Phần trăm  | 100%    | Còn hạn        | Boundary – giảm 100%   |

### Dữ liệu khác

- **8 phim** (đang chiếu + sắp chiếu)
- **4 rạp**, nhiều phòng chiếu
- **Hàng trăm ghế** (thường / VIP / đôi)
- **Nhiều suất chiếu** trong ngày hôm nay và các ngày tới
- **2 vé mẫu** đã thanh toán sẵn

---

## 7. Chức năng hệ thống

### 7.1. Khách hàng

| Chức năng              | Mô tả                                      | File chính          |
|------------------------|--------------------------------------------|---------------------|
| Đăng ký                | Validate họ tên, email, SĐT, username, MK  | `auth/dang_ky.php`  |
| Đăng nhập / Đăng xuất  | Session, kiểm tra tài khoản khóa           | `auth/dang_nhap.php`|
| Xem / Tìm / Lọc phim   | Theo tên, thể loại, trạng thái             | `danh_sach_phim.php`|
| Chi tiết phim          | Thông tin + lịch chiếu theo ngày/rạp       | `chi_tiet_phim.php` |
| Chọn ghế               | Sơ đồ ghế, hold 5p, max 8 ghế, AJAX        | `chon_ghe.php`      |
| Áp mã giảm giá         | Kiểm tra tồn tại / hết hạn / hết lượt      | Trong form đặt vé   |
| Thanh toán mô phỏng    | Thành công / Thất bại / Hủy + timeout 10p  | `thanh_toan.php`    |
| Vé của tôi             | Lịch sử, trạng thái vé                     | `ve_cua_toi.php`    |
| Hủy vé                 | Chỉ trước giờ chiếu ≥ 30 phút              | `huy_ve.php`        |

### 7.2. Quản trị viên

| Chức năng           | Mô tả                                              |
|---------------------|----------------------------------------------------|
| Dashboard           | Thống kê số phim, rạp, suất, vé, doanh thu, user  |
| CRUD Phim           | Thêm / Sửa / Xóa, validate dữ liệu                 |
| Quản lý Rạp         | Thêm / Xóa rạp                                     |
| Quản lý Phòng       | Thêm phòng → tự động tạo ghế theo hàng × cột      |
| Quản lý Suất chiếu  | Thêm suất + **kiểm tra xung đột** cùng phòng      |
| Quản lý Mã giảm giá | Thêm / Khóa / Mở mã                                |
| Quản lý Vé          | Xem danh sách đơn hàng                             |
| Quản lý User        | Khóa / Mở khóa tài khoản khách                     |
| Nhật ký hệ thống    | Xem log hành động (đặt vé, thanh toán, hủy…)      |

---

## 8. Luồng nghiệp vụ chính

```
[Đăng nhập]
    ↓
[Chọn phim] → [Xem chi tiết] → [Chọn suất chiếu]
    ↓
[Chọn ghế]  ←── Hold ghế 5 phút (AJAX)
    ↓           Countdown + Refresh sơ đồ
[Áp mã giảm giá] (tùy chọn)
    ↓
[Xác nhận → Tạo vé trạng thái "Chờ thanh toán"]
    ↓           Timeout 10 phút
[Thanh toán mô phỏng]
    ├── Thành công → Vé "Đã thanh toán"
    ├── Thất bại   → Hủy vé + giải phóng ghế
    └── Hủy / Timeout → Hủy vé + giải phóng ghế
    ↓
[Vé của tôi] → [Hủy vé] (nếu còn ≥ 30 phút trước giờ chiếu)
```

### Trạng thái vé

| Trạng thái         | Ý nghĩa                              |
|--------------------|--------------------------------------|
| `cho_thanh_toan`   | Đã giữ ghế, chờ thanh toán (10 phút)|
| `da_thanh_toan`    | Thanh toán thành công                |
| `da_su_dung`       | Đã vào rạp (có thể mở rộng sau)      |
| `da_huy`           | Đã hủy (user / timeout / thất bại)   |

### Trạng thái ghế trên sơ đồ

| Class CSS     | Ý nghĩa                          |
|---------------|----------------------------------|
| `available`   | Ghế trống, có thể chọn           |
| `selected`    | Đang được user hiện tại chọn     |
| `sold`        | Đã bán hoặc đang bị người khác giữ|
| `vip` / `doi` | Loại ghế (màu khác)              |
| `baotri`      | Ghế bảo trì, không chọn được     |

---

## 9. Tính năng phục vụ kiểm thử

### 9.1. Xung đột ghế (Concurrency)

- Khi user A chọn ghế → hệ thống **hold** ghế đó 5 phút.
- User B cùng lúc chọn ghế đó → bị từ chối (ghế hiển thị `sold`).
- Dùng **transaction + SELECT … FOR UPDATE** khi xác nhận đặt vé.
- Có thể test bằng 2 trình duyệt / 2 tab khác nhau.

### 9.2. Timeout

| Loại              | Thời gian | Hành vi khi hết hạn                |
|-------------------|-----------|------------------------------------|
| Hold ghế          | 5 phút    | Ghế được giải phóng, user phải chọn lại |
| Thanh toán        | 10 phút   | Vé tự chuyển `da_huy`, ghế giải phóng   |

### 9.3. Validation rõ ràng

- Frontend (JS) + Backend (PHP) đều validate.
- **Không** tự động sửa dữ liệu đầu vào sai.
- Thông báo lỗi bằng tiếng Việt, cụ thể từng trường.

### 9.4. Các tình huống lỗi có thể tái hiện

| Tình huống                         | Cách tái hiện                              |
|------------------------------------|--------------------------------------------|
| Đăng nhập sai MK / tài khoản       | Nhập sai trên form đăng nhập               |
| Email / username trùng             | Đăng ký với email đã có                    |
| Chọn ghế đã bán                    | 2 user cùng chọn 1 ghế                     |
| Không chọn ghế vẫn bấm thanh toán  | Form disable nút khi chưa chọn             |
| Mã giảm giá hết hạn                | Dùng mã `HETHAN`                           |
| Mã không tồn tại                   | Nhập mã bất kỳ                             |
| Hủy vé quá gần giờ chiếu           | Đặt vé suất sắp chiếu (< 30 phút)          |
| Truy cập admin khi là khách        | Vào `/admin/` bằng tài khoản khách         |
| Thanh toán timeout                 | Đặt vé rồi đợi > 10 phút                   |
| Suất chiếu trùng phòng             | Admin thêm 2 suất cùng phòng + giờ chồng   |

### 9.5. Kỹ thuật kiểm thử phù hợp

- **Equivalence Partitioning:** form đăng ký, đăng nhập, mã giảm giá
- **Boundary Value Analysis:** số ghế (0, 1, 8, 9), thời gian hủy (29/30 phút), % giảm giá (0, 100)
- **Decision Table:** chọn ghế + trạng thái suất + mã giảm giá + thanh toán
- **State Transition:** trạng thái vé
- **Concurrency Testing:** hold ghế
- **Security Testing:** CSRF, SQL Injection, phân quyền
- **Automation:** Selenium/Playwright trên luồng đặt vé

---

## 10. API nội bộ

Dùng cho AJAX và có thể dùng cho automation.

### GET `/api/ghe_status.php?suat_id={id}`

Trả về JSON trạng thái ghế của 1 suất chiếu.

```json
{
  "success": true,
  "ghe_da_ban": [1, 2, 5],
  "ghe_cua_toi": [3, 4],
  "het_han_hold": "2026-10-12 10:05:00",
  "server_time": "2026-10-12 10:00:00"
}
```

### POST `/api/hold_ghe.php`

Giữ ghế tạm thời.

**Body (form-data):**
- `suat_id`
- `ghe_ids[]` (mảng id ghế)

```json
{
  "success": true,
  "het_han": "2026-10-12 10:05:00"
}
```

---

## 11. Database

### Danh sách bảng

| Bảng            | Mô tả                              |
|-----------------|------------------------------------|
| `nguoi_dung`    | Tài khoản (admin / khách)          |
| `the_loai`      | Thể loại phim                      |
| `phim`          | Thông tin phim                     |
| `rap`           | Rạp chiếu                          |
| `phong_chieu`   | Phòng trong rạp                    |
| `ghe`           | Ghế trong phòng                    |
| `suat_chieu`    | Lịch chiếu                         |
| `ma_giam_gia`   | Mã khuyến mãi                      |
| `ve`            | Đơn vé (có `het_han_thanh_toan`)   |
| `chi_tiet_ve`   | Ghế thuộc vé                       |
| `thanh_toan`    | Lịch sử thanh toán                 |
| `ghe_giu`       | Hold ghế tạm thời (**mới v2**)     |
| `nhat_ky`       | Log hệ thống (**mới v2**)          |

### Quan hệ chính

```
nguoi_dung 1───* ve *───1 suat_chieu *───1 phim
                      *───1 phong_chieu *───1 rap
ve 1───* chi_tiet_ve *───1 ghe
suat_chieu + ghe ←── ghe_giu (hold)
```

---

## 12. Bảo mật

| Biện pháp              | Cách thực hiện                          |
|------------------------|-----------------------------------------|
| Hash mật khẩu          | `password_hash()` / `password_verify()` |
| SQL Injection          | PDO Prepared Statement                  |
| XSS                    | `htmlspecialchars()` (hàm `clean()`)    |
| CSRF                   | Token trên form POST quan trọng         |
| Phân quyền             | Kiểm tra `$_SESSION['vai_tro']` backend |
| Session                | PHP native session                      |
| Truy cập admin         | `yeuCauAdmin()` – redirect nếu không phải admin |

---

## 13. Hướng dẫn kiểm thử nhanh

### Test case mẫu – Đặt vé thành công

1. Đăng nhập `nguyenvana` / `123456`
2. Vào **Phim đang chiếu** → chọn 1 phim
3. Chọn 1 suất chiếu còn trống
4. Chọn 2 ghế thường → thấy countdown 5:00
5. (Tùy chọn) nhập mã `GIAM10`
6. Bấm **Tiếp tục thanh toán**
7. Bấm **Thanh toán thành công**
8. Vào **Vé của tôi** → thấy vé trạng thái “Đã thanh toán”

### Test case mẫu – Xung đột ghế

1. Mở 2 trình duyệt (hoặc 1 thường + 1 ẩn danh)
2. Đăng nhập 2 tài khoản khác nhau
3. Cùng chọn 1 suất chiếu
4. User A chọn ghế D5 → hold
5. User B refresh → ghế D5 hiện “Đã bán / Đang giữ”
6. User B không chọn được D5

### Test case mẫu – Timeout thanh toán

1. Đặt vé đến bước thanh toán
2. Đợi > 10 phút (hoặc sửa `het_han_thanh_toan` trong DB về quá khứ)
3. Refresh trang thanh toán → bị chuyển về “Vé của tôi” với thông báo hết hạn

### Test case mẫu – Hủy vé

1. Có vé “Đã thanh toán” của suất còn > 30 phút
2. Vào **Vé của tôi** → bấm **Hủy vé**
3. Xác nhận → vé chuyển “Đã hủy”, ghế được giải phóng

---

## 14. Reset dữ liệu test

Khi cần xóa sạch vé / giao dịch để test lại từ đầu:

```
http://localhost/dat-ve-xem-phim/reset_data.php?key=test123
```

Script sẽ:
- Xóa toàn bộ `ve`, `chi_tiet_ve`, `thanh_toan`, `ghe_giu`, `nhat_ky`
- Reset `da_su_dung` của mã giảm giá về 0
- **Không** xóa phim, rạp, ghế, user

> Chỉ dùng trên môi trường local / test. Không để key này trên production.

---

## 15. Xử lý lỗi thường gặp

| Lỗi                              | Nguyên nhân khả dĩ                    | Cách xử lý                              |
|----------------------------------|---------------------------------------|-----------------------------------------|
| Trang trắng / lỗi kết nối DB     | Sai host/user/pass hoặc chưa import DB| Kiểm tra `config/database.php` + phpMyAdmin |
| CSS/JS không load                | Sai đường dẫn (thiếu `/dat-ve-xem-phim/`)| Kiểm tra virtual host / thư mục htdocs  |
| Không đăng nhập được             | Chưa import lại DB sau khi đổi hash   | Import lại `database.sql`               |
| Ghế không hiện                   | Phòng chưa có ghế                     | Admin → Phòng chiếu → thêm phòng mới    |
| Lỗi CSRF                         | Session hết hạn / mở nhiều tab        | Refresh trang và thao tác lại           |
| Suất chiếu không hiện            | Suất đã qua hoặc trạng thái không `hoat_dong` | Kiểm tra ngày/giờ trong DB         |

---

## 16. Ghi chú phát triển

- Code có comment tiếng Việt ở các phần quan trọng.
- Tên biến/hàm: tiếng Việt không dấu hoặc tiếng Anh đơn giản, thống nhất.
- Không dùng framework để dễ đọc và dễ kiểm thử.
- Auto-cancel timeout chạy mỗi khi load trang (qua `functions.php`). Có thể chuyển sang cron nếu deploy thật.
- Poster phim hiện dùng placeholder CSS (không cần file ảnh).

---

## Phiên bản

| Version | Ngày       | Nội dung chính                                      |
|---------|------------|-----------------------------------------------------|
| 1.0     | 11/10/2026 | Bản đầu đủ chức năng đặt vé cơ bản                  |
| **2.0** | 12/10/2026 | Hold ghế, timeout TT, CSRF, logging, AJAX, admin mã giảm giá, nhật ký |

---

&copy; Bản quyền thuộc về **Nhóm 9 - Kiểm thử và đảm bảo chất lượng phần mềm**.
