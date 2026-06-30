# UTTMart - Microservices E-commerce Platform

[![CI/CD](https://github.com/<your-username>/uttmart/actions/workflows/deploy.yml/badge.svg)](https://github.com/<your-username>/uttmart/actions/workflows/deploy.yml)

Dự án UTTMart là hệ thống thương mại điện tử được xây dựng theo kiến trúc microservices với PHP và MySQL.

## 🏗️ Kiến trúc

- **Frontend**: HTML, CSS, JavaScript (Vanilla)
- **Backend**: 8 Microservices PHP
- **Database**: MySQL (nhiều database riêng biệt)
- **API Gateway**: Nginx
- **Container**: Docker & Docker Compose
- **CI/CD**: GitHub Actions

## 📋 Yêu cầu hệ thống

- **Docker**: Version 20.10+
- **Docker Compose**: Version 2.0+
- **RAM**: Tối thiểu 4GB
- **Disk**: Tối thiểu 2GB trống
- **OS**: Windows/Linux/Mac

## 🚀 Cài đặt và chạy

### Bước 1: Clone repository
```bash
git clone <repository-url>
cd pjfinal
```

### Bước 2: Cấu hình biến môi trường
```bash
# Copy file mẫu
cp .env.example .env

# Chỉnh sửa file .env với thông tin thực tế
# (mật khẩu DB, SMTP, API key, ...)
nano .env    # Linux/Mac
notepad .env # Windows
```

### Bước 3: Khởi động hệ thống
```bash
# Chạy tất cả services
docker-compose up -d

# Xem logs (tùy chọn)
docker-compose logs -f
```

### Bước 4: Kiểm tra trạng thái
```bash
# Kiểm tra containers đang chạy
docker-compose ps

# Chờ database khởi tạo xong (khoảng 30-60 giây)
```

### Bước 5: Truy cập hệ thống
- **Trang chủ**: http://localhost:8080
- **Admin Dashboard**: http://localhost:8080/manage.html

## 📁 Cấu trúc dự án

```
pjfinal/
├── README.md              # Hướng dẫn này
├── .gitignore             # Git ignore rules
├── .dockerignore          # Docker ignore rules
├── .env.example           # Mẫu biến môi trường
├── .env                   # Biến môi trường (KHÔNG commit)
├── docker-compose.yml     # Docker orchestration
├── .github/
│   └── workflows/
│       └── deploy.yml     # CI/CD pipeline
├── databases/             # SQL schema files
├── infra/                 # Docker configurations
│   ├── gateway/           # Nginx API gateway
│   └── php-apache/        # PHP Apache base image
├── services/              # Microservices
│   ├── user-service/
│   ├── product-service/
│   ├── order-service/
│   ├── customer-service/
│   ├── payment-service/
│   ├── employee-service/
│   ├── inventory-service/
│   ├── attendance-service/
│   └── expense-service/
└── web/                   # Frontend static files
    ├── *.html             # Pages
    ├── main.js            # Shared JavaScript
    └── images/            # Static assets
```

## 🔄 CI/CD Pipeline

Pipeline tự động chạy khi push code lên nhánh `main`:

```
Push to main
    │
    ▼
┌─────────────────────┐
│  CI: Build & Test   │
│  • Build images     │
│  • Start services   │
│  • Health check     │
└─────────┬───────────┘
          │ ✅ Pass
          ▼
┌─────────────────────┐
│  CD: Deploy to VPS  │
│  • SSH vào server   │
│  • git pull         │
│  • docker compose   │
│    up -d --build    │
└─────────────────────┘
```

### Cấu hình GitHub Secrets

Vào **Settings > Secrets and variables > Actions** trên GitHub repo và thêm các secrets sau:

| Secret Name | Mô tả |
|---|---|
| `MYSQL_ROOT_PASSWORD` | Mật khẩu root MySQL |
| `USER_DB_PASSWORD` | Mật khẩu user-service DB |
| `PRODUCT_DB_PASSWORD` | Mật khẩu product-service DB |
| `ORDER_DB_PASSWORD` | Mật khẩu order-service DB |
| `INVENTORY_DB_PASSWORD` | Mật khẩu inventory-service DB |
| `EMPLOYEE_DB_PASSWORD` | Mật khẩu employee-service DB |
| `ATTENDANCE_DB_PASSWORD` | Mật khẩu attendance-service DB |
| `EXPENSE_DB_PASSWORD` | Mật khẩu expense-service DB |
| `CUSTOMER_DB_PASSWORD` | Mật khẩu customer-service DB |
| `PAYMENT_DB_PASSWORD` | Mật khẩu payment-service DB |
| `INTERNAL_API_KEY` | Internal API Key |
| `SMTP_HOST` | SMTP server host |
| `SMTP_PORT` | SMTP server port |
| `SMTP_USER` | SMTP username |
| `SMTP_PASSWORD` | SMTP password |
| `MAIL_FROM` | Email gửi |
| `MAIL_FROM_NAME` | Tên hiển thị email |
| `GEMINI_API_KEY` | Google Gemini API Key |
| `VPS_HOST` | Địa chỉ IP máy chủ deploy |
| `VPS_USER` | Username SSH |
| `VPS_SSH_KEY` | Private key SSH |

## 🔧 Troubleshooting

### Lỗi thường gặp:

#### 1. Port 8080 đã được sử dụng
```bash
# Kiểm tra port
netstat -ano | findstr :8080

# Thay đổi port trong docker-compose.yml
ports:
  - "8081:80"  # Thay 8080 thành 8081
```

#### 2. Database connection failed
```bash
# Kiểm tra database container
docker-compose ps

# Restart database
docker-compose restart order-db
```

#### 3. Permission denied
```bash
# Trên Linux/Mac
sudo chmod -R 755 .

# Trên Windows - chạy Command Prompt as Administrator
```

#### 4. Out of memory
```bash
# Tăng RAM cho Docker Desktop
# Hoặc giảm services chạy đồng thời
docker-compose up -d user-service product-service order-service
```

### Logs debugging:
```bash
# Xem lỗi PHP
docker-compose logs order-service

# Xem lỗi database
docker-compose logs order-db
```

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. Docker và Docker Compose đã cài đặt
2. File `.env` đã được tạo từ `.env.example`
3. Port 8080 không bị chiếm
4. Đủ RAM (4GB+)
5. Firewall không chặn port

## 🎯 Features

- ✅ Microservices Architecture
- ✅ User Authentication & Authorization
- ✅ Product Catalog & Inventory
- ✅ Shopping Cart & Wishlist
- ✅ Order Management
- ✅ Payment Integration (MoMo)
- ✅ Admin Dashboard
- ✅ Employee Management
- ✅ Attendance Tracking
- ✅ Expense Management
- ✅ Multi-tenant Database Design
- ✅ CI/CD Pipeline with GitHub Actions

---

**Happy coding! 🚀**