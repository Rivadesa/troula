"""
Extrae del catálogo PDF de Retrátate las fotos de cada experiencia y complemento
y las deja listas en storage/app/public/{experiencias,complementos}/<slug>.jpg.

Uso (desde la raíz del proyecto):

    python scripts/imagenes-catalogo.py "C:\\ruta\\RETRATATE EVENTOS CATALOGO 2026.pdf"

Después, para que el catálogo apunte a ellas:

    php artisan catalogo:asignar-imagenes

DECISIÓN: las fotos NO se versionan en el repositorio (es público y en varias
aparecen personas identificables de eventos reales). Este script permite
regenerarlas cuando haga falta a partir del PDF original.

El mapeo es (página, índice de imagen dentro de la página) -> slug. Los índices
salen de recorrer `page.images` con pypdf; si el cliente entrega un PDF nuevo
hay que revisarlos.
"""

import io
import os
import sys

from pypdf import PdfReader
from PIL import Image

# (pagina, indice) -> slug
EXPERIENCIAS = {
    (3, 1): "fotomaton-solo",
    (4, 1): "fotomaton-photocall-tela",
    (5, 1): "fotomaton-photocall-lentejuelas",
    (7, 5): "fotomaton-estructura-neon",
    (8, 4): "fotomaton-beauty-glam",
    (9, 3): "fotomaton-beauty-glam-premium",
    (10, 4): "espejo-magico",
    (11, 1): "plataforma-360",
    (12, 1): "aereo-360",
    (13, 1): "cabina-hinchable-led-xxl",
    (14, 2): "cabana-rustica",
}

COMPLEMENTOS = {
    (15, 5): "estructura",
    (15, 1): "fondo-jardin",
    (17, 2): "shimmer-wall",
    (16, 1): "neon",
    (15, 8): "sofa",
    (15, 16): "alfombra",
    (18, 3): "audiolibro-firmas",
    (19, 1): "audiolibro-video",
    (23, 1): "letras-iniciales",
    (23, 4): "letras-love",
    (23, 2): "pack-letras",
    (20, 1): "glitter-corner-1",
    (20, 4): "glitter-corner-2",
    (21, 0): "hora-loca-a",
    (21, 3): "hora-loca-b",
    (21, 4): "hora-loca-c",
    (24, 2): "mesa-dulce-golosinas",
    (24, 5): "mesa-dulce-donuts",
    (25, 1): "kiosko-moita-troula",
    (26, 1): "puesto-palomitero",
    (26, 2): "puesto-algodonero",
    (26, 3): "puesto-hot-dog",
    (27, 1): "futbolin",
    (27, 2): "arcade",
    (28, 3): "portafoto-iman",
    (28, 5): "invitaciones-madera",
}

MAX_LADO = 1400
CALIDAD = 85


def extraer(pdf: str, destino_base: str) -> None:
    reader = PdfReader(pdf)
    guardadas, fallidas = 0, []

    for carpeta, mapa in (("experiencias", EXPERIENCIAS), ("complementos", COMPLEMENTOS)):
        destino = os.path.join(destino_base, carpeta)
        os.makedirs(destino, exist_ok=True)

        for (pagina, indice), slug in mapa.items():
            try:
                datos = reader.pages[pagina - 1].images[indice].data
                img = Image.open(io.BytesIO(datos)).convert("RGB")
            except Exception as e:  # noqa: BLE001
                fallidas.append(f"{carpeta}/{slug} (p{pagina} #{indice}): {e}")
                continue

            img.thumbnail((MAX_LADO, MAX_LADO))
            ruta = os.path.join(destino, f"{slug}.jpg")
            img.save(ruta, "JPEG", quality=CALIDAD, optimize=True)
            guardadas += 1
            print(f"  {carpeta}/{slug}.jpg  ({img.width}x{img.height})")

    print(f"\nGuardadas {guardadas} imágenes en {destino_base}")
    if fallidas:
        print("FALLIDAS:")
        for f in fallidas:
            print("  -", f)


if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.exit("Falta la ruta del PDF del catálogo.")

    raiz = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    extraer(sys.argv[1], os.path.join(raiz, "storage", "app", "public"))
