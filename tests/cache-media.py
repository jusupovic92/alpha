"""Acquire referenced public product photos for the packaged Alpha demo."""
from pathlib import Path
from urllib.request import Request,urlopen
from html.parser import HTMLParser
from io import BytesIO
import base64
from PIL import Image
root=Path(__file__).resolve().parents[1]; dest=root/'public/assets/alpha'
sources={
 'pads.jpg':'https://asdetailing.ba/wp-content/uploads/2026/07/chemicalworkz-performance-pads-promotion-box-6tlg-75mm-300x300.webp',
 'polish.jpg':'https://asdetailing.ba/wp-content/uploads/2026/10/Therapy-300x300.webp',
 'microfiber.jpg':'https://asdetailing.ba/wp-content/uploads/2025/08/10.2-300x200.jpg',
 'shampoo.jpg':'https://asdetailing.ba/wp-content/uploads/2025/11/201G01_Wash_N_Wax-300x300.webp'}
def get(url):
 with urlopen(Request(url,headers={'User-Agent':'Mozilla/5.0'}),timeout=30) as res:return res.read()
class Images(HTMLParser):
 def __init__(self):super().__init__();self.images=[]
 def handle_starttag(self,tag,attrs):
  if tag=='img':self.images.append(dict(attrs))
parser=Images();parser.feed(get('https://asdetailing.ba/product-tag/mipa/').decode('utf-8'))
for image in parser.images:
 if 'UV lak' in image.get('alt',''):
  sources['clearcoat.jpg']=image.get('data-src') or image.get('src');break
if 'clearcoat.jpg' not in sources:raise RuntimeError('Mipa product photo not found')
for filename,url in sources.items():
 target=dest/filename
 if not target.is_file():Image.open(BytesIO(get(url))).convert('RGB').save(target,'JPEG',quality=93)
 print('ALPHA_MEDIA '+filename+' '+base64.b64encode(target.read_bytes()).decode())
