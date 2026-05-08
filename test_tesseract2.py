import pytesseract
from PIL import Image

img_path = "/Users/ks/Desktop/planExtract/web/storage/wordtemp/WhatsApp Image 2026-04-27 at 3.46.02 PM.jpeg"
img = Image.open(img_path)
text = pytesseract.image_to_string(img, lang='eng')
print(text)
