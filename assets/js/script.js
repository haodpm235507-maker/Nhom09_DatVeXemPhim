/**
 * CineBook - JavaScript chính
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto dismiss alert sau 5 giây
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});

/**
 * Format số tiền
 */
function formatTien(soTien) {
    return new Intl.NumberFormat('vi-VN').format(soTien) + ' VNĐ';
}

/**
 * Hiển thị loading
 */
function showLoading() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner-border text-light" style="width:3rem;height:3rem;"></div>';
        document.body.appendChild(overlay);
    }
    overlay.classList.add('show');
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.classList.remove('show');
}

/**
 * Validate form đăng ký
 */
function validateDangKy(form) {
    let valid = true;
    const errors = [];

    const hoTen = form.ho_ten.value.trim();
    const email = form.email.value.trim();
    const sdt = form.so_dien_thoai.value.trim();
    const username = form.ten_dang_nhap.value.trim();
    const password = form.mat_khau.value;
    const confirm = form.xac_nhan_mat_khau.value;

    // Họ tên
    if (hoTen.length < 2 || hoTen.length > 100) {
        errors.push('Họ tên phải từ 2 đến 100 ký tự.');
        valid = false;
    }

    // Email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errors.push('Email không hợp lệ.');
        valid = false;
    }

    // SĐT
    const sdtRegex = /^(0[3|5|7|8|9])[0-9]{8}$/;
    if (!sdtRegex.test(sdt)) {
        errors.push('Số điện thoại không hợp lệ (10 số, bắt đầu 03/05/07/08/09).');
        valid = false;
    }

    // Username
    if (username.length < 4 || username.length > 50) {
        errors.push('Tên đăng nhập phải từ 4 đến 50 ký tự.');
        valid = false;
    }
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        errors.push('Tên đăng nhập chỉ chứa chữ, số và gạch dưới.');
        valid = false;
    }

    // Mật khẩu
    if (password.length < 6) {
        errors.push('Mật khẩu phải có ít nhất 6 ký tự.');
        valid = false;
    }

    if (password !== confirm) {
        errors.push('Xác nhận mật khẩu không khớp.');
        valid = false;
    }

    if (!valid) {
        alert(errors.join('\n'));
    }
    return valid;
}

/**
 * Validate form đăng nhập
 */
function validateDangNhap(form) {
    const username = form.ten_dang_nhap.value.trim();
    const password = form.mat_khau.value;

    if (!username || !password) {
        alert('Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.');
        return false;
    }
    return true;
}