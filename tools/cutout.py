#!/usr/bin/env python3
"""Potong background foto mobil -> PNG transparan untuk hero Parc Ferme.

Pakai:
    pip install rembg onnxruntime pillow numpy scipy
    python tools/cutout.py <url-atau-file> public/images/cars/<id>.png

Model (~180 MB) diunduh otomatis sekali saja pada run pertama.
"""
import io
import sys
import urllib.request

import numpy as np
from PIL import Image, ImageFilter
from rembg import new_session, remove
from scipy import ndimage

MAX_W = 1800   # lebar maksimum hasil (px)
PAD = 0.02     # ruang kosong di sekeliling mobil (proporsi)


def load(src: str) -> Image.Image:
    if src.startswith(("http://", "https://")):
        req = urllib.request.Request(src, headers={"User-Agent": "ParcFerme/1.0 (cutout script)"})
        with urllib.request.urlopen(req, timeout=60) as r:
            return Image.open(io.BytesIO(r.read())).convert("RGB")
    return Image.open(src).convert("RGB")


def clean_alpha(img: Image.Image) -> Image.Image:
    a = np.array(img.split()[3])

    # Buang bintik/pulau kecil sisa background (antena, tiang, dll).
    mask = a > 24
    labels, n = ndimage.label(mask)
    if n > 1:
        sizes = ndimage.sum(mask, labels, range(1, n + 1))
        keep = [i + 1 for i, s in enumerate(sizes) if s >= sizes.max() * 0.02]
        a = np.where(np.isin(labels, keep), a, 0).astype(np.uint8)

    # Kikis 1px + haluskan tepi supaya tidak ada halo terang dari background lama.
    alpha = Image.fromarray(a).filter(ImageFilter.MinFilter(3)).filter(ImageFilter.GaussianBlur(0.8))
    img.putalpha(alpha)
    return img


def trim(img: Image.Image) -> Image.Image:
    box = img.split()[3].point(lambda v: 255 if v > 24 else 0).getbbox()
    if not box:
        return img
    x0, y0, x1, y1 = box
    px, py = int((x1 - x0) * PAD), int((y1 - y0) * PAD)
    box = (max(0, x0 - px), max(0, y0 - py), min(img.width, x1 + px), min(img.height, y1 + py))
    return img.crop(box)


def main() -> None:
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    src, dst = sys.argv[1], sys.argv[2]

    photo = load(src)
    out = remove(photo, session=new_session("isnet-general-use"))
    out = trim(clean_alpha(out.convert("RGBA")))
    if out.width > MAX_W:
        out = out.resize((MAX_W, round(out.height * MAX_W / out.width)), Image.LANCZOS)
    out.save(dst, optimize=True)
    print(f"OK {dst} ({out.width}x{out.height})")


if __name__ == "__main__":
    main()
