# Face Recognition Service

Service Flask stateless untuk aplikasi Laravel ABL Attendance.

Alur:

1. Laravel mengirim foto wajah pegawai ke `/api/face/embedding`.
2. Service mendeteksi satu wajah dan mengembalikan face embedding.
3. Saat absensi, Laravel mengirim embedding referensi dan foto kamera ke `/api/face/verify`.
4. Service mengembalikan hasil cocok/tidak cocok beserta jarak wajah.

## Instalasi

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python app.py
```

Buka `http://127.0.0.1:5000`.

## Docker

Dari root project Laravel:

```bash
docker compose -f docker-compose.face.yml up --build
```

Laravel yang berjalan di host bisa memakai konfigurasi:

```env
ATTENDANCE_FACE_SERVICE_URL=http://127.0.0.1:5000
```

## Endpoint

- `GET /api/health`
- `POST /api/face/embedding` dengan multipart `image` atau JSON `{ "image": "data:image/jpeg;base64,..." }`
- `POST /api/face/verify` dengan JSON `{ "reference_embedding": [...], "image": "data:image/jpeg;base64,...", "tolerance": 0.5 }`

## Catatan

- Service ini tidak menyimpan database pegawai atau absensi.
- Nilai toleransi default ada di `FACE_TOLERANCE` pada `app.py`. Makin kecil nilainya makin ketat.
- Foto registrasi harus berisi satu wajah yang jelas.
