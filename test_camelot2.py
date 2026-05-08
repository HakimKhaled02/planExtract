import pytesseract
from PIL import Image
import camelot
import tempfile
import sys
import os
import pandas as pd

pd.set_option('display.max_columns', None)
pd.set_option('display.max_rows', None)
pd.set_option('display.width', 1000)

img_path = "/Users/ks/Desktop/planExtract/web/storage/wordtemp/WhatsApp Image 2026-04-27 at 3.47.16 PM.jpeg"
img = Image.open(img_path)
pdf_bytes = pytesseract.image_to_pdf_or_hocr(img, extension='pdf')

with tempfile.NamedTemporaryFile(suffix='.pdf', delete=False) as temp_pdf:
    temp_pdf.write(pdf_bytes)
    pdf_path = temp_pdf.name

try:
    tables = camelot.read_pdf(pdf_path, flavor='stream', pages='1')
    if tables.n > 0:
        print(tables[0].df.to_string())
finally:
    os.remove(pdf_path)
