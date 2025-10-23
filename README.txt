🚌 Bus Ticket Platform

Bus Ticket Platform, PHP + SQLite kullanılarak geliştirilmiş, çok katmanlı (MVC yapısında) bir bilet satış ve yönetim sistemidir.
Kullanıcılar, firmalar ve yöneticiler için farklı roller sunar.
Ayrıca proje, Docker üzerinden kolayca kurulabilir ve taşınabilir hale getirilmiştir.

🧩 Özellikler

👥 Kullanıcı, Firma ve Admin rolleri
🚌 Sefer yönetimi (ekleme, düzenleme, silme, kapasite)
🎫 Bilet oluşturma, iptal ve PDF çıktısı (FPDF kütüphanesi ile)
💰 Kupon ve indirim sistemi
🧾 SQLite veritabanı (
🔒 CSRF koruması, session yönetimi, güvenli giriş
🐳 Docker desteği – tek komutla çalıştırılabilir
📦 Tamamen bağımsız yapı (Apache + PHP + SQLite)

⚙️ Kullanılan Teknolojiler
Katman	              Teknoloji
Backend	              PHP 8.2 (PDO + SQLite)
Frontend	            HTML5, Bootstrap 5.3, JavaScript
Database	            SQLite 
PDF	                  FPDF
Server	              Apache (php:8.2-apache Docker image)
Containerization	    Docker & Docker Compose

🚀 Kurulum 
git clone https://github.com/mehmetdeli06/bus-ticket-platform.git
cd bus-ticket-platform
docker compose build
docker compose up -d
http://localhost:8000

bus-ticket-platform/
│
├── app/
│   ├── config/         # Veritabanı, oturum, güvenlik ayarları
│   ├── controllers/    # MVC controller’ları
│   ├── lib/            # FPDF vb. yardımcı kütüphaneler
│   └── views/          # Arayüz sayfaları 
│
├── public/             # Giriş noktası 
├── storage/            # SQLite veritabanı, PDF, session, cache
│   └── app.db
│
├── Dockerfile
├── docker-compose.yml
├── .gitignore
├── LICENSE
└── README.md



