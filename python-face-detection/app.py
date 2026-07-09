from __future__ import annotations

import base64
import binascii
import io
import json
from pathlib import Path

import face_recognition
import numpy as np
from flask import Flask, jsonify, request
from PIL import Image, UnidentifiedImageError
from werkzeug.exceptions import RequestEntityTooLarge


BASE_DIR = Path(__file__).resolve().parent
FACE_TOLERANCE = 0.5
MAX_IMAGE_BYTES = 3 * 1024 * 1024
MAX_IMAGE_PIXELS = 4_000_000

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = MAX_IMAGE_BYTES
Image.MAX_IMAGE_PIXELS = MAX_IMAGE_PIXELS


def image_bytes_to_array(image_bytes: bytes) -> np.ndarray:
    if len(image_bytes) > MAX_IMAGE_BYTES:
        raise ValueError("Ukuran gambar wajah maksimal 3 MB.")

    try:
        image = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    except UnidentifiedImageError as exc:
        raise ValueError("Format gambar wajah tidak valid.") from exc

    if image.width * image.height > MAX_IMAGE_PIXELS:
        raise ValueError("Resolusi gambar wajah terlalu besar.")

    return np.array(image)


def data_url_to_bytes(data_url: str) -> bytes:
    if "," not in data_url:
        raise ValueError("Format gambar kamera tidak valid.")

    header, payload = data_url.split(",", 1)
    if "base64" not in header:
        raise ValueError("Gambar kamera harus dikirim sebagai base64.")

    try:
        image_bytes = base64.b64decode(payload, validate=True)
    except binascii.Error as exc:
        raise ValueError("Payload base64 gambar tidak valid.") from exc

    if len(image_bytes) > MAX_IMAGE_BYTES:
        raise ValueError("Ukuran gambar wajah maksimal 3 MB.")

    return image_bytes


def request_image_bytes() -> bytes:
    uploaded_image = request.files.get("image")
    if uploaded_image:
        image_bytes = uploaded_image.read()

        if len(image_bytes) > MAX_IMAGE_BYTES:
            raise ValueError("Ukuran gambar wajah maksimal 3 MB.")

        return image_bytes

    payload = request.get_json(silent=True) or {}
    image_data = payload.get("image", "")
    if not image_data:
        raise ValueError("Gambar wajah wajib dikirim.")

    return data_url_to_bytes(image_data)


def extract_face_embedding(image_bytes: bytes) -> np.ndarray:
    image = image_bytes_to_array(image_bytes)
    locations = face_recognition.face_locations(image, model="hog")

    if not locations:
        raise ValueError("Wajah tidak terdeteksi. Gunakan foto yang terang dan menghadap kamera.")
    if len(locations) > 1:
        raise ValueError("Terdeteksi lebih dari satu wajah. Gunakan foto satu orang saja.")

    encodings = face_recognition.face_encodings(image, known_face_locations=locations)
    if not encodings:
        raise ValueError("Embedding wajah gagal dibuat. Coba foto lain yang lebih jelas.")

    return encodings[0]


def parse_reference_embedding(raw_embedding: object) -> np.ndarray:
    if isinstance(raw_embedding, str):
        raw_embedding = json.loads(raw_embedding)

    if not isinstance(raw_embedding, list) or not raw_embedding:
        raise ValueError("Reference embedding tidak valid.")

    return np.array(raw_embedding, dtype=np.float64)


@app.get("/api/health")
def health():
    return jsonify({"ok": True, "service": "face-recognition"})


@app.errorhandler(RequestEntityTooLarge)
def request_entity_too_large(_error):
    return jsonify({"ok": False, "message": "Ukuran gambar wajah maksimal 3 MB."}), 413


@app.post("/api/face/embedding")
def create_embedding():
    try:
        embedding = extract_face_embedding(request_image_bytes())
    except ValueError as exc:
        return jsonify({"ok": False, "message": str(exc)}), 400

    return jsonify({"ok": True, "embedding": embedding.tolist()})


@app.post("/api/face/verify")
def verify_face():
    payload = request.get_json(silent=True) or {}

    try:
        reference_embedding = parse_reference_embedding(payload.get("reference_embedding"))
        live_embedding = extract_face_embedding(request_image_bytes())
        tolerance = float(payload.get("tolerance", FACE_TOLERANCE))
        distance = float(face_recognition.face_distance([reference_embedding], live_embedding)[0])
    except (TypeError, ValueError) as exc:
        return jsonify({"ok": False, "message": str(exc)}), 400

    return jsonify(
        {
            "ok": True,
            "matched": distance <= tolerance,
            "distance": round(distance, 6),
            "tolerance": tolerance,
        }
    )


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
