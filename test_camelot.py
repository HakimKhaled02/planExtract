import pytesseract
from PIL import Image
import camelot
import tempfile
import sys
import os

img_path = "/Users/ks/Desktop/planExtract/web/storage/wordtemp/WhatsApp Image 2026-04-27 at 3.47.16 PM.jpeg"
img = Image.open(img_path)
pdf_bytes = pytesseract.image_to_pdf_or_hocr(img, extension='pdf')

with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as temp_pdf:
    temp_pdf.write(pdf_bytes)
    pdf_path = temp_pdf.name

try:
    print(f"Testing Camelot on {pdf_path}")
    # Lattice won't work on OCR pdf (no actual lines). Stream will try to guess text blocks.
    tables = camelot.read_pdf(pdf_path, flavor='stream', pages='1')
    print(f"Total tables extracted: {tables.n}")
    if tables.n > 0:
        for i, table in enumerate(tables):
            print(f"\n--- Table {i+1} ---")
            print(table.df)
finally:
    os.remove(pdf_path)
