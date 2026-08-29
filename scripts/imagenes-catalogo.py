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

# Modelos concretos que el cliente elige dentro de una máquina: telas del
# photocall (p4), lentejuelas (p5), estructuras (p6), neones (p16) y sofás (p6).
# Van a la misma carpeta que el resto de complementos.
VARIANTES = {
    # Telas del photocall
    (4, 2): "tela-tropical-palmeras",
    (4, 3): "tela-gran-viaje",
    (4, 4): "tela-madera-banderines",
    (4, 5): "tela-love-is-in-the-air",
    (4, 6): "tela-tropical-rosa",
    (4, 7): "tela-globos",
    (4, 8): "tela-flamenco-love",
    (4, 9): "tela-topos-dorados",
    (4, 10): "tela-corazones-colores",
    (4, 11): "tela-nuestra-boda",
    (4, 13): "tela-madera-luces",
    (4, 14): "tela-corazones-rosas",
    (4, 15): "tela-hojas-acuarela",
    (4, 16): "tela-rosas-fucsia",
    (4, 17): "tela-flores-silvestres",
    (4, 18): "tela-rosa-corazones",
    (4, 19): "tela-hojas-doradas",
    (4, 20): "tela-londres",
    (4, 21): "tela-helados",
    # Lentejuelas
    (5, 1): "lentejuelas-plata",
    (5, 2): "lentejuelas-holografica",
    # Estructuras (los nombres salen del propio texto de la página 6)
    (6, 9): "estructura-fondo-jardin-vertical",
    (6, 10): "estructura-jardin-flores",
    (6, 11): "estructura-hexagono-madera",
    (6, 12): "estructura-triangulo-madera",
    (6, 15): "estructura-cuadrado-metal",
    (6, 16): "estructura-lentejuelas",
    (6, 17): "estructura-shimmer-wall",
    # Sofás
    (6, 23): "sofa-rosa-palo",
    (6, 24): "sofa-chester-marron",
    (6, 25): "sofa-azul",
    (6, 26): "sofa-malva",
    (6, 27): "sofa-amarillo",
    (6, 28): "sofa-verde",
    (6, 29): "sofa-rosa-claro",
    (6, 30): "sofa-rojo",
    # Neones
    (16, 1): "neon-fin-del-mundo-arco",
    (16, 2): "neon-fin-del-mundo",
    (16, 3): "neon-sempre-ti",
    (16, 4): "neon-me-quedo-contigo",
    (16, 5): "neon-si-quiero",
    (16, 6): "neon-a-los-locos",
    (16, 8): "neon-querote",
    (16, 9): "neon-contigo-al-fin-del-mundo",
    (16, 10): "neon-mejor-equipo",
    (16, 11): "neon-que-no-sea-contigo",
    (16, 12): "neon-siempre-seras-tu",
    (16, 13): "neon-la-vida-es-una-verbena",
    (16, 14): "neon-ata-o-infinito",
    (16, 15): "neon-cala-e-bicame",
    (16, 16): "neon-aqui-empeza",
    (16, 17): "neon-contigo-todo",
    (16, 18): "neon-el-amor-todo-locura",
    (16, 19): "neon-love-is-love",
    (16, 20): "neon-juntos-es-mejor",
    (16, 21): "neon-aqui-se-lia-parda",
}

MAX_LADO = 1400
CALIDAD = 85


def extraer(pdf: str, destino_base: str) -> None:
    reader = PdfReader(pdf)
    guardadas, fallidas = 0, []

    for carpeta, mapa in (
        ("experiencias", EXPERIENCIAS),
        ("complementos", COMPLEMENTOS),
        ("complementos", VARIANTES),
    ):
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
