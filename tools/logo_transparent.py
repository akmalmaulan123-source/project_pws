#!/usr/bin/env python3
"""Ubah logo merek berlatar polos (JPG/PNG) menjadi PNG transparan.

Pakai:
    pip install pillow numpy scipy
    python tools/logo_transparent.py                       # semua logo (jpg/jpeg/webp) di public/images/brands
    python tools/logo_transparent.py public/images/brands/bmw.jpg
    python tools/logo_transparent.py public/images/brands/toyota.png --holes

Opsi:
    --holes[=0.25]  Hapus juga putih yang TERKURUNG di dalam logo (ruang di dalam lambang, lubang huruf
                    O/A/R). Angkanya batas luas lubang (proporsi gambar, default 0.25). Hati-hati: bagian putih
                    yang memang bagian logo (mis. kuadran putih BMW) ikut hilang, jadi pakai per logo.
    --tol=40        Toleransi "putih" untuk --holes (default 18). Naikkan (30-60) kalau bagian dalam logo
                    putih kekuningan/abu sangat terang atau hasil kompresi JPG membuatnya tidak rata.
    --light=0.8     Ubah semua piksel di bawah 80% tinggi gambar jadi warna teks terang (untuk tulisan hitam).

Hasil: <nama>.png di folder yang sama. Aslinya tidak diubah; kalau inputnya sudah .png,
aslinya disalin dulu ke public/images/brands/_original/.
(Brand::logo_src membaca svg > png > webp > jpg, jadi PNG otomatis dipakai.)
"""
import sys
from pathlib import Path

import numpy as np
from PIL import Image
from scipy import ndimage

MAX_W = 512
PAD = 0.04
EXT = {".jpg", ".jpeg", ".webp"}
# Pengaturan khusus per merek (nama file tanpa ekstensi)
#   holes_below: hanya di bagian bawah gambar (proporsi tinggi) lubang huruf kecil yang terkurung ikut
#                dihapus, untuk tulisan merek (BENTLEY, PORSCHE). Bagian lain tidak disentuh karena
#                sorot terang pada logo logam akan ikut hilang.
#   checker: latar berpola kotak-kotak abu/putih (hasil screenshot); hanya petak latar yang besar dihapus,
#            sorot terang kecil pada logam tetap
#   lighten_below: tulisan hitam di bagian bawah gambar diubah jadi terang supaya terbaca di latar gelap
SPECIAL = {
    "mercedes-amg": {"checker": True},
    "bentley": {"holes_below": 0.62, "lighten_below": 0.62},
    "porsche": {"holes_below": 0.82, "lighten_below": 0.82},
    "morgan": {"lighten_below": 0.72},
}
LIGHT = (217, 214, 207)  # warna teks situs
HOLE_AREA = 0.01


def estimate_bg(rgb: np.ndarray) -> np.ndarray:
    edge = np.concatenate([rgb[0], rgb[-1], rgb[:, 0], rgb[:, -1]]).reshape(-1, 3)
    return np.median(edge, axis=0)


HOLE_STRICT = 18  # jarak warna maksimum dari warna latar agar sebuah lubang dianggap "putih murni"


def make_transparent(img: Image.Image, opt: dict) -> Image.Image:
    # Input PNG yang sudah punya transparansi: bagian transparan dianggap latar, sisanya ditempel ke putih.
    pre_bg = None
    if img.mode in ("RGBA", "LA", "P") and "A" in img.convert("RGBA").getbands():
        rgba_in = img.convert("RGBA")
        a_in = np.asarray(rgba_in)[..., 3]
        if (a_in < 250).any():
            pre_bg = a_in < 128
            flat = Image.new("RGBA", rgba_in.size, (255, 255, 255, 255))
            flat.alpha_composite(rgba_in)
            img = flat
    rgb = np.asarray(img.convert("RGB")).astype(np.float32)
    h, w, _ = rgb.shape
    bg = estimate_bg(rgb)
    dist = np.sqrt(((rgb - bg) ** 2).sum(axis=2))  # 0..441

    if opt.get("checker"):
        mx, mn = rgb.max(axis=2), rgb.min(axis=2)
        cand = (mn >= 228) & (mx - mn <= 7)
    else:
        cand = dist <= 40

    labels, n = ndimage.label(cand)
    border = np.unique(np.concatenate([labels[0], labels[-1], labels[:, 0], labels[:, -1]]))
    is_bg = np.isin(labels, border[border > 0])
    if pre_bg is not None:
        is_bg |= pre_bg

    # Latar yang terkurung (lubang huruf, sela logo)
    if opt.get("checker") and n:
        sizes = ndimage.sum(cand, labels, range(1, n + 1))
        big = [i + 1 for i, s in enumerate(sizes) if s >= 0.02 * h * w]
        is_bg |= np.isin(labels, big)
    if opt.get("holes"):
        # Lubang putih murni yang terkurung. Memakai ambang ketat supaya sorot krom (abu terang) tidak ikut terhapus.
        strict = dist <= opt.get("tol", HOLE_STRICT)
        sl, sn = ndimage.label(strict)
        if sn:
            sizes = ndimage.sum(strict, sl, range(1, sn + 1))
            hole = [i + 1 for i, a in enumerate(sizes) if a <= opt["holes"] * h * w]
            is_bg |= np.isin(sl, hole)

    if opt.get("holes_below") and n and not opt.get("checker"):
        y0 = int(h * opt["holes_below"])
        sizes = ndimage.sum(cand, labels, range(1, n + 1))
        tops = ndimage.find_objects(labels)
        small = [
            i + 1 for i, s in enumerate(sizes)
            if s <= HOLE_AREA * h * w and tops[i] is not None and tops[i][0].start >= y0
        ]
        is_bg |= np.isin(labels, small)

    if opt.get("verbose"):
        leftover = (dist <= 60) & ~is_bg
        lab2, n2 = ndimage.label(leftover)
        big = sorted((int(a) for a in ndimage.sum(leftover, lab2, range(1, n2 + 1))), reverse=True)[:3] if n2 else []
        print(
            f"  latar terdeteksi RGB={tuple(int(v) for v in bg)}; dihapus {is_bg.mean() * 100:.1f}% piksel; "
            f"sisa area terang di dalam logo (3 terbesar, piksel)={big}"
        )
        if big and big[0] > 0.005 * h * w:
            hint = "--holes --tol=40" if opt.get("holes") else "--holes"
            print(f"  ! Masih ada area terang besar di dalam logo. Kalau itu seharusnya transparan, coba: {hint}")

    # Tepi halus: piksel di pinggir latar dihitung alpha-nya dari jaraknya ke warna latar,
    # lalu warnanya dibersihkan dari sisa warna latar (tidak ada halo putih).
    ring = ndimage.binary_dilation(is_bg, iterations=2) & ~is_bg
    alpha = np.where(is_bg, 0.0, 1.0)
    t0, t1 = 40.0, 150.0
    a_edge = np.clip((dist - t0) / (t1 - t0), 0.0, 1.0)
    alpha = np.where(ring, np.minimum(1.0, a_edge + 0.15), alpha)
    alpha = np.where(is_bg, 0.0, alpha)

    out = rgb.copy()
    safe = np.maximum(alpha, 0.05)[..., None]
    unmix = (rgb - bg * (1 - alpha[..., None])) / safe
    out = np.where(ring[..., None], np.clip(unmix, 0, 255), out)

    if opt.get("lighten_below"):
        y0 = int(h * opt["lighten_below"])
        out[y0:][alpha[y0:] > 0] = LIGHT

    rgba = np.dstack([out, alpha * 255]).astype(np.uint8)
    im = Image.fromarray(rgba, "RGBA")

    box = im.split()[3].point(lambda v: 255 if v > 16 else 0).getbbox()
    if box:
        x0, y0, x1, y1 = box
        px, py = int((x1 - x0) * PAD), int((y1 - y0) * PAD)
        im = im.crop((max(0, x0 - px), max(0, y0 - py), min(w, x1 + px), min(h, y1 + py)))
    if im.width > MAX_W:
        im = im.resize((MAX_W, round(im.height * MAX_W / im.width)), Image.LANCZOS)
    return im


def main() -> None:
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    flags = [a for a in sys.argv[1:] if a.startswith("--")]
    cli = {}
    for fl in flags:
        key, _, val = fl[2:].partition("=")
        if key == "holes":
            cli["holes"] = float(val) if val else 0.25
        elif key == "tol":
            cli["tol"] = float(val) if val else 40.0
        elif key == "light":
            cli["lighten_below"] = float(val) if val else 0.8
        else:
            sys.exit(f"Opsi tidak dikenal: {fl}\n{__doc__}")

    if args:
        files = [Path(a) for a in args]
    else:
        root = Path(__file__).resolve().parent.parent / "public" / "images" / "brands"
        files = sorted(p for p in root.iterdir() if p.suffix.lower() in EXT)

    for f in files:
        dst = f.with_suffix(".png")
        if dst.resolve() == f.resolve():  # input sudah .png: simpan salinan aslinya dulu
            backup = f.parent / "_original"
            backup.mkdir(exist_ok=True)
            if not (backup / f.name).exists():
                (backup / f.name).write_bytes(f.read_bytes())
        im = make_transparent(Image.open(f), {**SPECIAL.get(f.stem, {}), **cli, "verbose": True})
        im.save(dst, optimize=True)
        print(f"OK {dst.name} ({im.width}x{im.height})")


if __name__ == "__main__":
    main()
