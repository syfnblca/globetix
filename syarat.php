<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat & Ketentuan - GlobeTix</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, rgba(11, 107, 134, 0.8), rgba(0, 0, 0, 0.6));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal {
            background: linear-gradient(145deg, #ffffff, #f0f8ff);
            border-radius: 0;
            box-shadow: none;
            width: 100vw;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
            top: 0;
            left: 0;
            transform: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            to { transform: scale(1); }
        }
        .modal-header {
            background: linear-gradient(90deg, #0b6b86, #0d7a9a);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: rotate(90deg);
        }
        .modal-content {
            padding: 30px;
            background: linear-gradient(to bottom, #ffffff, #f9f9f9);
        }
        .modal-content h3 {
            color: #0b6b86;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 600;
            border-left: 4px solid #0b6b86;
            padding-left: 15px;
            position: relative;
        }
        .modal-content h3::before {
            content: '📋';
            position: absolute;
            left: -25px;
            top: 0;
            font-size: 16px;
        }
        .modal-content p {
            line-height: 1.7;
            margin-bottom: 20px;
            color: #333;
            text-align: justify;
        }
        .modal-content ul {
            margin-left: 25px;
            padding-left: 0;
        }
        .modal-content li {
            margin-bottom: 8px;
            color: #555;
        }
        .modal-content li::marker {
            color: #0b6b86;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .modal-content {
                padding: 20px;
            }
            .modal-header h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="modal">
        <div class="modal-header">
            <h2>Syarat & Ketentuan GlobeTix</h2>
            <button class="close-btn" onclick="window.close()">&times;</button>
        </div>
        <div class="modal-content">
            <h3>1. Penggunaan Layanan</h3>
            <p>Dengan menggunakan layanan GlobeTix, Anda setuju untuk mematuhi semua syarat dan ketentuan yang tercantum di sini. Layanan ini disediakan untuk pemesanan tiket bus secara online.</p>

            <h3>2. Pemesanan dan Pembayaran</h3>
            <p>Semua pemesanan harus dilakukan melalui platform GlobeTix. Pembayaran harus dilakukan sesuai dengan instruksi yang diberikan. Kursi akan dikunci selama 10 menit untuk menyelesaikan pembayaran.</p>

            <h3>3. Kebijakan Pembatalan</h3>
            <p>Pembatalan dapat dilakukan sesuai dengan kebijakan yang berlaku. Silakan hubungi layanan pelanggan untuk informasi lebih lanjut.</p>

            <h3>4. Tanggung Jawab Pengguna</h3>
            <p>Anda bertanggung jawab atas keakuratan informasi yang diberikan saat pemesanan. Pastikan data penumpang sesuai dengan identitas resmi.</p>

            <h3>5. Perubahan Jadwal</h3>
            <p>GlobeTix berhak mengubah jadwal perjalanan karena alasan tertentu. Pengguna akan diberitahu melalui email atau SMS.</p>

            <h3>6. Privasi Data</h3>
            <p>Data pribadi Anda akan dijaga kerahasiaannya sesuai dengan kebijakan privasi GlobeTix.</p>

            <h3>7. Batasan Tanggung Jawab</h3>
            <p>GlobeTix tidak bertanggung jawab atas kerugian yang timbul dari penggunaan layanan ini di luar kendali kami.</p>

            <h3>8. Perubahan Syarat</h3>
            <p>GlobeTix berhak mengubah syarat dan ketentuan ini kapan saja tanpa pemberitahuan sebelumnya.</p>

            <p>Dengan menyetujui syarat ini, Anda mengonfirmasi bahwa Anda telah membaca dan memahami semua ketentuan di atas.</p>
        </div>
    </div>
</body>
</html>
