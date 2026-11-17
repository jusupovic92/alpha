import assert from 'node:assert/strict';
import {spawn, spawnSync} from 'node:child_process';
import {writeFileSync, existsSync, unlinkSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import net from 'node:net';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const php=process.env.PHP_BINARY||'php';
const config=path.join(root,'config/local.php');
assert(!existsSync(config),'HTTP tests refuse to overwrite existing configuration');
const dbname=process.env.TEST_DB_NAME||'webshop_test';
assert(/^webshop_test(?:_[a-z0-9]+)?$/.test(dbname));
const quote=s=>"'"+s.replaceAll('\\','\\\\').replaceAll("'","\\'")+"'";
const cfg={db_host:(process.env.TEST_DB_HOST||'127.0.0.1')+';port='+(process.env.TEST_DB_PORT||'33317'),db_name:dbname,db_user:process.env.TEST_DB_USER||'root',db_pass:process.env.TEST_DB_PASSWORD||'local_test_only'};
writeFileSync(config,'<?php return ['+Object.entries(cfg).map(([k,v])=>quote(k)+'=>'+quote(v)).join(',')+'];');
const probe=net.createServer();await new Promise(resolve=>probe.listen(0,'127.0.0.1',resolve));const port=probe.address().port;await new Promise(resolve=>probe.close(resolve));
const base='http://127.0.0.1:'+port+'/';
const server=spawn(php,['-S','127.0.0.1:'+port,'-t',path.join(root,'public')],{stdio:['ignore','pipe','pipe']});
let logs='';server.stderr.on('data',x=>logs+=x);server.stdout.on('data',x=>logs+=x);
const jar=()=>({cookie:''});const admin=jar(),buyer=jar(),guest=jar();
async function request(url,session=guest,data){
 const res=await fetch(base+url,{redirect:'manual',headers:{...(session.cookie?{Cookie:session.cookie}:{}),...(data?{'Content-Type':'application/x-www-form-urlencoded'}:{})},...(data?{method:'POST',body:new URLSearchParams(data)}:{})});
 const c=res.headers.get('set-cookie');if(c)session.cookie=c.split(';')[0];
 const text=await res.text();assert(!/Fatal error|Parse error|Warning:/.test(text),text);return {status:res.status,text,location:res.headers.get('location')};
}
const csrf=html=>{const v=html.match(/name="csrf" value="([a-f0-9]+)"/);assert(v,'CSRF token exists');return v[1];};
try{
 let ready=false;for(let i=0;i<40;i++){assert.equal(server.exitCode,null,'PHP server stays running');try{await request('');ready=true;break;}catch{await new Promise(r=>setTimeout(r,100));}}assert(ready,'PHP server ready');
 assert.equal((await request('')).status,200);
 assert.equal((await request('admin.php?page=users')).status,200,'anonymous sees login only');
 assert.equal((await request('admin.php',guest,{action:'login',email:'bad@example.test',password:'wrong',csrf:'bad'})).status,403,'CSRF blocked');
 let login=await request('admin.php',admin);let result=await request('admin.php',admin,{action:'login',email:'admin@example.test',password:'test-password-123',csrf:csrf(login.text)});assert.equal(result.status,303);
 for(const page of ['dashboard','products','products&create=1','categories','inventory','reports','exits','exits&create=1','orders','order&id=2','settings','branding','themes','backups','mail','delivery','users','roles','audit','print&id=2','print&type=stock']){
  result=await request('admin.php?page='+page,admin);assert.equal(result.status,200,page);assert(!result.text.includes('<h1>Prijava</h1>'),page+' authenticated');
 }
 let products=await request('admin.php?page=products',admin);
 result=await request('admin.php?page=products',admin,{action:'product',id:'0',name:'<script>alert(1)</script>',sku:'HTTP-TEST',description:'Test opis',price:'9.99',category_id:'1',image_url:'',active:'on',featured:'on',csrf:csrf(products.text)});assert.equal(result.status,303);
 const home=await request('',buyer);assert(home.text.includes('&lt;script&gt;alert(1)&lt;/script&gt;'),'product title escaped');assert(!home.text.includes('<script>alert(1)</script>'));
 // Validate a real multipart image upload and reject an executable disguised as an image.
 const imageForm=new FormData();
 const imageFields={action:'product',id:'0',name:'Slika test',sku:'HTTP-IMAGE',description:'Slika',price:'4.20',category_id:'1',image_url:'',active:'on',featured:'on',csrf:csrf(products.text)};
 for(const [k,v] of Object.entries(imageFields))imageForm.set(k,v);
 imageForm.set('image',new Blob([Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a41cAAAAASUVORK5CYII=','base64')],{type:'image/png'}),'fixture.png');
 let imageResponse=await fetch(base+'admin.php?page=products',{method:'POST',body:imageForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(imageResponse.status,303);await imageResponse.text();
 const uploadedRegistry=await request('admin.php?page=products&q=HTTP-IMAGE',admin);const bannerProduct=uploadedRegistry.text.match(/class="product-table-name" href="\?page=products&id=(\d+)/)[1];
 imageForm.set('id',bannerProduct);imageForm.set('hero_selected','on');imageForm.delete('image');imageForm.set('hero_banner',new Blob([Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a41cAAAAASUVORK5CYII=','base64')],{type:'image/png'}),'banner.png');
 imageResponse=await fetch(base+'admin.php?page=products',{method:'POST',body:imageForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(imageResponse.status,303);await imageResponse.text();
 const bannerHome=await request('',buyer);assert(bannerHome.text.includes('class="campaign-banner"')&&bannerHome.text.includes('class="campaign-cta"'),'uploaded banner appears with product link');
 const bannerEdit=await request('admin.php?page=products&id='+bannerProduct,admin);assert(/name="hero_banner_url"[^>]+uploads\//.test(bannerEdit.text),'banner address saved');
 imageForm.set('hero_banner',new Blob(['<?php echo "bad";'],{type:'image/png'}),'bad.php');imageResponse=await fetch(base+'admin.php?page=products',{method:'POST',body:imageForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(imageResponse.status,200);assert((await imageResponse.text()).includes('Koristite JPG'));
 imageForm.delete('hero_banner');imageForm.delete('hero_selected');imageForm.set('id','0');imageForm.set('sku','HTTP-BAD-IMAGE');imageForm.set('image',new Blob(['<?php echo "bad";'],{type:'image/png'}),'bad.php');
 imageResponse=await fetch(base+'admin.php?page=products',{method:'POST',body:imageForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(imageResponse.status,200);assert((await imageResponse.text()).includes('Koristite JPG'));
 products=await request('admin.php?page=products&q=HTTP-TEST',admin);const id=products.text.match(/class="product-table-name" href="\?page=products&id=(\d+)/)[1];
 let inventory=await request('admin.php?page=inventory',admin);
 result=await request('admin.php?page=inventory',admin,{action:'stock',id,quantity:'3',note:'HTTP stock fixture',csrf:csrf(inventory.text)});assert.equal(result.status,303);
 const cartToken=csrf(home.text);
 assert.equal((await request('index.php?page=add',buyer,{id,qty:'2',csrf:cartToken})).status,303);
 const checkout=await request('index.php?page=checkout',buyer);assert.equal(checkout.status,200);const token=checkout.text.match(/name="checkout_token" value="([a-f0-9]+)"/)[1];
 result=await request('index.php?page=checkout',buyer,{csrf:csrf(checkout.text),checkout_token:token,customer_name:'HTTP kupac',email:'http@example.test',phone:'061123456',address:'Adresa 1',city:'Kakanj',note:'HTTP test',shipping_method:'1',payment_method:'cod'});assert.equal(result.status,303);
 assert((await request('index.php?page=success',buyer)).text.includes('Hvala na narudžbi'));
 assert((await request('admin.php?page=orders&q=HTTP kupac',admin)).text.includes('HTTP kupac'));
 const orderSearch=await request('admin.php?page=orders&q=HTTP kupac',admin);const orderId=orderSearch.text.match(/href="\?page=order&id=(\d+)/)[1];
 let orderScreen=await request('admin.php?page=order&id='+orderId,admin);assert(orderScreen.text.includes('U pripremi za slanje'));
 assert.equal((await request('admin.php?page=order&id='+orderId,admin,{action:'payment',id:orderId,payment_status:'paid',note:'HTTP uplata unaprijed',csrf:csrf(orderScreen.text)})).status,303);
 orderScreen=await request('admin.php?page=order&id='+orderId,admin);assert(orderScreen.text.includes('Naplata je evidentirana, ali otprema nije potvrđena.'));
 const failedDispatch=await request('admin.php?page=order&id='+orderId,admin,{action:'status',id:orderId,status:'shipped',courier_name:'',tracking_number:'',dispatch_note:'Predano pakovanje',csrf:csrf(orderScreen.text)});assert.equal(failedDispatch.status,200);assert(failedDispatch.text.includes('Odaberite ili unesite kurirsku službu'));
 assert.equal((await request('admin.php?page=order&id='+orderId,admin,{action:'delivery_prepare',id:orderId,delivery_id:'1',courier_name:'HTTP ekspres',delivery_cost:'10.00',delivery_terms:'HTTP uslovi 24-48 sati',delivery_payer:'customer',note:'Priprema HTTP',csrf:csrf(orderScreen.text)})).status,303);
 assert.equal((await request('admin.php?page=order&id='+orderId,admin,{action:'status',id:orderId,status:'shipped',courier_name:'HTTP ekspres',tracking_number:'',dispatch_note:'Paket predan kuriru',csrf:csrf(orderScreen.text)})).status,303);
 orderScreen=await request('admin.php?page=order&id='+orderId,admin);assert(orderScreen.text.includes('status-shipped">Poslano')&&orderScreen.text.includes('status-paid">Naplaćeno')&&orderScreen.text.includes('Razduženo i naplaćeno'));
 assert.equal((await request('admin.php?page=order&id='+orderId,admin,{action:'shipment',id:orderId,courier_name:'HTTP ekspres',tracking_number:'HTTP-TRACK-123',dispatch_note:'Broj dostavljen naknadno',csrf:csrf(orderScreen.text)})).status,303);
 const shippingList=await request('admin.php?page=print&id='+orderId,admin);assert(shippingList.text.includes('HTTP ekspres')&&shippingList.text.includes('HTTP-TRACK-123'));
 assert((await request('admin.php?page=orders&status=shipped&payment=paid&courier=HTTP ekspres',admin)).text.includes('href="?page=order&id='+orderId+'"'));
 assert(!(await request('admin.php?page=orders&status=preparing&q=HTTP kupac',admin)).text.includes('href="?page=order&id='+orderId+'"'));
 const exitForm=await request('admin.php?page=exits&create=1',admin);const exitToken=exitForm.text.match(/name="exit_token" value="([a-f0-9]+)"/)[1];
 const exitInput={action:'stock_exit',product_id:id,quantity:'1',reason:'write_off',document_ref:'HTTP-OT-1',exit_note:'HTTP otpis oštećenja',exit_token:exitToken,csrf:csrf(exitForm.text)};
 const noNote=await request('admin.php?page=exits&create=1',admin,{...exitInput,exit_note:''});assert.equal(noNote.status,200);assert(noNote.text.includes('Provjerite polje: exit_note'));
 const createdExit=await request('admin.php?page=exits',admin,exitInput);assert.equal(createdExit.status,303);const exitId=new URL(createdExit.location,base).searchParams.get('id');
 const exitDetail=await request('admin.php?page=exits&id='+exitId,admin);assert(exitDetail.text.includes('HTTP otpis oštećenja'));
 assert((await request('admin.php?page=exits&reason=write_off&q=HTTP-OT-1',admin)).text.includes('href="?page=exits&id='+exitId+'"'));
 assert((await request('admin.php?page=exits&export=csv&reason=write_off',admin)).text.includes('HTTP-OT-1'));
 assert.equal((await request('admin.php?page=exits&id='+exitId,admin,{action:'exit_reverse',id:exitId,exit_note:'Testna korekcija, artikal vraćen',csrf:csrf(exitDetail.text)})).status,303);
 assert((await request('admin.php?page=inventory',admin,{action:'stock',id,quantity:'-1',note:'Nedopušten generički izlaz',csrf:csrf(exitDetail.text)})).text.includes('Za izlaz robe koristite Drugi izlazi robe'));
 assert.equal((await request('admin.php?page=products',admin,{...imageFields,id:bannerProduct,hero_banner_url:'javascript:alert(1)'})).status,200,'unsafe banner URL rejected');
 assert.equal((await request('admin.php?page=products',admin,{...imageFields,id:bannerProduct,hero_banner_url:''})).status,303,'banner removed and automatic layout restored');
 // New homepage slots are independent of ordinary featured products.
 const heroIds=[];
 for(let i=1;i<=5;i++){
   const fields={action:'product',id:'0',name:'Hero candidate '+i,sku:'HERO-'+i,description:'Detaljan opis i specifikacije '+i,price:'29.90',purchase_price:'12.50',low_stock_threshold:'4',category_id:'1',image_url:'',gallery_urls:i===1?'https://example.test/detail-1.png\nhttps://example.test/detail-2.png':'',hero_selected:'on',...(i<5?{active:'on'}:{}),csrf:csrf(products.text)};
   assert.equal((await request('admin.php?page=products',admin,fields)).status,303);
   const registry=await request('admin.php?page=products&q=HERO-'+i,admin);
   heroIds.push(registry.text.match(/class="product-table-name" href="\?page=products&id=(\d+)/)[1]);
 }
 const carousel=await request('',buyer);
 assert.equal((carousel.text.match(/data-slide-index=/g)||[]).length,3,'exactly three active slides');
 const heroMarkup=carousel.text.slice(carousel.text.indexOf('<section class="showcase campaign-showcase editorial-showcase"'),carousel.text.indexOf('<div class="service-strip editorial-services"'));
 assert(heroMarkup.includes('Hero candidate 4')&&heroMarkup.includes('Hero candidate 3')&&heroMarkup.includes('Hero candidate 2'));
 assert(!heroMarkup.includes('Hero candidate 1')&&!heroMarkup.includes('Hero candidate 5'),'older and inactive selections excluded');
 const filtered=await request('admin.php?page=products&placement=hero_live',admin);
 const visibleIds=[...filtered.text.matchAll(/class="product-table-name" href="\?page=products&id=(\d+)/g)].map(m=>m[1]);
 assert.deepEqual(visibleIds,[heroIds[3],heroIds[2],heroIds[1]],'admin filter matches current slides');
 const detailHome=await request('',buyer);assert(detailHome.text.includes('editorial-showcase')&&detailHome.text.includes('editorial-services'),'editorial homepage rendered');
 const galleryHero=await request('admin.php?page=products',admin,{action:'product',id:heroIds[3],name:'Hero candidate 4',sku:'HERO-4',description:'Detaljan opis',price:'29.90',category_id:'1',image_url:'https://example.test/main.png',gallery_urls:'https://example.test/detail-a.png\nhttps://example.test/detail-b.png\nhttps://example.test/detail-c.png',active:'on',hero_selected:'on',csrf:csrf(products.text)});assert.equal(galleryHero.status,303);
 const editorialHome=await request('',buyer);const detailTiles=editorialHome.text.match(/class="hero-detail-images"[^]*?<\/div>/)?.[0]||'';assert.equal((detailTiles.match(/<img /g)||[]).length,2,'only first two gallery details rendered');assert(detailTiles.includes('detail-a.png')&&detailTiles.includes('detail-b.png')&&!detailTiles.includes('detail-c.png'));
 const detail=await request('index.php?page=product&id='+heroIds[0],buyer);assert.equal(detail.status,200);assert(detail.text.includes('Detaljan opis i specifikacije 1'));assert.equal((detail.text.match(/data-gallery-src=/g)||[]).length,2,'first gallery image becomes main when main is empty');
 assert(!detail.text.includes('purchase_price')&&!detail.text.includes('12,50 KM'),'private cost not exposed');
 assert.equal((await request('index.php?page=product&id='+heroIds[4],buyer)).status,404,'inactive detail hidden');
 assert.equal((await request('index.php?page=product&id=99999999',buyer)).status,404,'unknown product rejected');
 for(let i=4;i>=1;i--){
  assert.equal((await request('admin.php?page=products',admin,{action:'product',id:heroIds[i-1],name:'Hero candidate '+i,sku:'HERO-'+i,description:'Detaljan opis',price:'29.90',category_id:'1',image_url:'',active:'on',csrf:csrf(products.text)})).status,303);
  const rotated=await request('',buyer);
  const slides=(rotated.text.match(/<article class="hero-slide [^"]*"/g)||[]).length;
  assert.equal(slides,i-1,'slider handles '+(i-1)+' active candidates');
  if(i===1)assert(rotated.text.includes('intro-slide'),'empty hero uses configured introduction');
 }
 // Category placement and order are managed through the existing category form.
 assert.equal((await request('admin.php?page=categories',admin,{action:'category',id:'0',name:'Home Category',slug:'home-category',show_on_home:'on',sort_order:'-10',csrf:csrf(products.text)})).status,303);
 assert((await request('',buyer)).text.includes('Home Category'));
 for(const format of ['stock','movements','payments'])assert.equal((await request('admin.php?page=reports&export='+format,admin)).status,200,'CSV '+format);
 assert.equal((await request('admin.php?page=reports&from=2026-02-31',admin)).status,422,'invalid report date blocked');
 const injection={action:'product',id:'0',name:'=HYPERLINK("bad")',sku:'=BAD-SKU',description:'CSV test',price:'1.00',category_id:'1',image_url:'',csrf:csrf(products.text)};
 assert.equal((await request('admin.php?page=products',admin,injection)).status,303);
 assert((await request('admin.php?page=reports&export=stock',admin)).text.includes("'=BAD-SKU"),'CSV formula injection neutralized');
 // A user with no permissions must be denied both pages and direct POST actions.
 const seed=spawnSync(php,['-r',`$c=require 'config/local.php';$d=new PDO('mysql:host='.$c['db_host'].';dbname='.$c['db_name'],$c['db_user'],$c['db_pass']);$d->exec("INSERT INTO roles(name,permissions) VALUES('Bez dozvola','[]')");$s=$d->prepare('INSERT INTO users(role_id,name,email,password_hash) VALUES(?,?,?,?)');$s->execute([$d->lastInsertId(),'Restricted','restricted@example.test',password_hash('test-password-123',PASSWORD_DEFAULT)]);$s->execute([3,'Stock','stock@example.test',password_hash('test-password-123',PASSWORD_DEFAULT)]);$s->execute([4,'Finance','finance@example.test',password_hash('test-password-123',PASSWORD_DEFAULT)]);`],{cwd:root,encoding:'utf8'});assert.equal(seed.status,0,seed.stderr);
 const restricted=jar();login=await request('admin.php',restricted);assert.equal((await request('admin.php',restricted,{action:'login',email:'restricted@example.test',password:'test-password-123',csrf:csrf(login.text)})).status,303);
 const dashboard=await request('admin.php',restricted);
 for(const page of ['users','roles','orders','inventory','reports','reports&export=stock','exits','exits&export=csv','print&id=2','print&type=stock','settings'])assert.equal((await request('admin.php?page='+page,restricted)).status,403,'restricted '+page);
 assert.equal((await request('admin.php',restricted,{action:'stock',id,quantity:999,note:'unauthorized',csrf:csrf(dashboard.text)})).status,403,'direct POST permission enforcement');
 const staffSessions={};for(const [email,allowed,denied] of [['stock@example.test','stock','payments'],['finance@example.test','payments','stock']]){
  const staff=jar(),screen=await request('admin.php',staff);assert.equal((await request('admin.php',staff,{action:'login',email,password:'test-password-123',csrf:csrf(screen.text)})).status,303);
  assert.equal((await request('admin.php?page=reports&export='+allowed,staff)).status,200,'authorized '+allowed+' export');
  assert.equal((await request('admin.php?page=reports&export='+denied,staff)).status,403,'unauthorized '+denied+' export');
  staffSessions[email]=staff;
 }
 const stockStaff=staffSessions['stock@example.test'],financeStaff=staffSessions['finance@example.test'];
 const staffExitForm=await request('admin.php?page=exits&create=1',stockStaff),staffToken=staffExitForm.text.match(/name="exit_token" value="([a-f0-9]+)"/)[1];
 const personalSale={action:'stock_exit',product_id:id,quantity:'1',reason:'personal_sale',unit_price:'12.30',recipient:'HTTP primalac',document_ref:'HTTP-SALE',exit_note:'Predano lično',exit_token:staffToken,exit_payment:'paid',csrf:csrf(staffExitForm.text)};
 assert.equal((await request('admin.php?page=exits',stockStaff,personalSale)).status,403,'stock role cannot mark sale paid');
 const unpaidSale=await request('admin.php?page=exits',stockStaff,{...personalSale,exit_payment:'unpaid'});assert.equal(unpaidSale.status,303);const saleId=new URL(unpaidSale.location,base).searchParams.get('id');
 const financeSale=await request('admin.php?page=exits&id='+saleId,financeStaff);assert.equal(financeSale.status,200);
 assert.equal((await request('admin.php?page=exits&id='+saleId,financeStaff,{action:'exit_payment',id:saleId,payment_status:'paid',exit_note:'HTTP potvrda naplate',csrf:csrf(financeSale.text)})).status,303);
 assert.equal((await request('admin.php?page=exits&id='+exitId,financeStaff)).status,403,'finance cannot read write-off document');
 assert((await request('admin.php?page=reports&export=payments',financeStaff)).text.includes('Licna prodaja'),'personal sale included in financial export');

 const deliveries=await request('admin.php?page=delivery',admin);
 const deliveryFields={action:'delivery',id:'0',name:'HTTP delivery',cost:'8.50',courier_name:'HTTP Courier',terms:'Rok 48h',payer:'sender',active:'on',csrf:csrf(deliveries.text)};
 assert.equal((await request('admin.php?page=delivery',admin,deliveryFields)).status,303);
 const deliveryList=await request('admin.php?page=delivery',admin); const exactMethods=[...deliveryList.text.matchAll(/<tr><td><strong>HTTP delivery<\/strong>[^]*?href="\?page=delivery&id=(\d+)"/g)];assert.equal(exactMethods.length,1);const newMethod=exactMethods[0][1];
 assert((await request('admin.php?page=delivery&id='+newMethod,admin)).text.includes('Rok 48h'));
 assert.equal((await request('admin.php?page=delivery',admin,{...deliveryFields,id:newMethod,terms:'Novi rok 72h'})).status,303);
 assert.equal((await request('admin.php?page=delivery',stockStaff,deliveryFields)).status,403,'warehouse cannot edit delivery catalog');
 assert.equal((await request('admin.php?page=delivery',admin,{action:'delivery_delete',id:newMethod,note:'Ukinuto',csrf:csrf(deliveries.text)})).status,303);
 assert(!(await request('admin.php?page=delivery',admin)).text.includes('<strong>HTTP delivery</strong>'));assert((await request('admin.php?page=delivery&deleted=1',admin)).text.includes('<strong>HTTP delivery</strong>'));
 assert((await request('admin.php?page=print&id='+orderId,admin)).text.includes('HTTP uslovi 24-48 sati'),'print keeps prepared conditions');

 const priceForm=await request('admin.php?page=products',admin);const saleFields={action:'product',id:'0',name:'Sniženi artikal',sku:'HTTP-PRICE-SALE',description:'Akcijski artikal',price:'89.00',compare_price:'139.00',category_id:'1',image_url:'',featured:'on',active:'on',hero_selected:'on',csrf:csrf(priceForm.text)};
 assert.equal((await request('admin.php?page=products',admin,saleFields)).status,303);const saleRegistry=await request('admin.php?page=products&q=HTTP-PRICE-SALE',admin);const saleProduct=saleRegistry.text.match(/class="product-table-name" href="\?page=products&id=(\d+)/)[1];
 const saleDetail=await request('index.php?page=product&id='+saleProduct,buyer);assert(saleDetail.text.includes('<del>139,00 KM</del>')&&saleDetail.text.includes('Ušteda 50,00 KM'));
 const saleHome=await request('',buyer);assert(saleHome.text.includes('sale-badge')&&saleHome.text.includes('<del>139,00 KM</del>'),'sale shown in cards and hero');
 assert((await request('admin.php?page=products',admin,{...saleFields,id:saleProduct,compare_price:'80.00'})).text.includes('Stara cijena mora biti veća'));
 assert.equal((await request('admin.php?page=mail',stockStaff)).status,403,'warehouse cannot read mail administration');
 const mailScreen=await request('admin.php?page=mail',admin);assert(mailScreen.text.includes('SMTP lozinka')&&!/name="mail_password"[^>]+value="[^" ]+"/.test(mailScreen.text));
 assert.equal((await request('admin.php?page=mail',admin,{action:'mail_settings',csrf:csrf(mailScreen.text),mail_transport:'smtp',mail_security:'tls',mail_port:'587',mail_host:'smtp.example.test',mail_username:'shop@example.test',mail_from:'shop@example.test',mail_from_name:'Shop',mail_recipients:'pack@example.test; ship@example.test',mail_base_url:'https://shop.example.test'})).status,303,'mail settings saved in administration without sending');
 const badMail=await request('admin.php?page=mail',admin,{action:'mail_settings',csrf:csrf(mailScreen.text),mail_transport:'smtp',mail_security:'tls',mail_port:'587',mail_recipients:'bad'});assert(badMail.text.includes('Neispravna adresa primaoca'));

 const autoProduct=await request('admin.php?page=products',admin,{...saleFields,sku:'',name:'Automatski artikal',id:'0',compare_price:''});assert.equal(autoProduct.status,303);const autoRegistry=await request('admin.php?page=products&q=Automatski artikal',admin);const autoId=autoRegistry.text.match(/class="product-table-name" href="\?page=products&id=(\d+)/)[1];const autoEdit=await request('admin.php?page=products&id='+autoId,admin);assert(/name="sku"[^>]+value="\d+"/.test(autoEdit.text),'missing SKU assigned automatically');
 const settingsScreen=await request('admin.php?page=settings',admin);assert(settingsScreen.text.includes('Veličina teksta u prodavnici')&&settingsScreen.text.includes('Veličina teksta u administraciji'));
 const fontSettings={action:'settings',shop_name:'WEBSHOP test',hero_title:'Test naslov',hero_text:'Test',accent_color:'#167655',store_font_scale:'130',admin_font_scale:'140',csrf:csrf(settingsScreen.text)};assert.equal((await request('admin.php?page=settings',admin,fontSettings)).status,303);assert((await request('',buyer)).text.includes('--font-scale:1.3'));assert((await request('admin.php',admin)).text.includes('--font-scale:1.4'));
 assert((await request('admin.php?page=settings',admin,{...fontSettings,store_font_scale:'999'})).text.includes('između 100 i 140'));

 const themes=await request('admin.php?page=themes',admin);assert(themes.text.includes('Smaragdna')&&themes.text.includes('Mornarska'));assert.equal((await request('admin.php?page=themes',admin,{action:'theme',theme:'navy',csrf:csrf(themes.text)})).status,303);assert((await request('admin.php',admin)).text.includes('data-theme="navy"'));assert((await request('admin.php?page=themes',admin,{action:'theme',theme:'invalid',csrf:csrf(themes.text)})).text.includes('Odaberite postojeću temu'));
 assert((await request('',buyer)).text.includes('data-store-theme="classic"'),'old installations retain classic storefront without SQL migration');
 for(const theme of ['ocean','sand','plum','minimal','classic']){assert.equal((await request('admin.php?page=themes',admin,{action:'store_theme',theme,csrf:csrf(themes.text)})).status,303);for(const url of ['','?page=cart','?page=product&id=1'])assert((await request(url,buyer)).text.includes('data-store-theme="'+theme+'"'));assert((await request('admin.php',admin)).text.includes('data-theme="navy"'),'store theme independent of admin');}
 assert((await request('admin.php?page=themes',admin,{action:'store_theme',theme:'<script>',csrf:csrf(themes.text)})).text.includes('Odaberite postojeću temu prodavnice'));
 assert.equal((await request('admin.php?page=themes',stockStaff,{action:'store_theme',theme:'ocean',csrf:csrf(staffExitForm.text)})).status,403);
 assert((await request('assets/store-themes.css',buyer)).text.includes('[data-store-theme=ocean]'));
 const branding=await request('admin.php?page=branding',admin);assert(branding.text.includes('Logo i brend'));assert.equal((await request('admin.php?page=branding',stockStaff)).status,403);
 assert.equal((await request('admin.php?page=branding',stockStaff,{action:'branding',brand_logo_width:'280',brand_slogan:'Test',csrf:csrf(staffExitForm.text)})).status,403);
 const brandForm=new FormData();for(const [k,v] of Object.entries({action:'branding',brand_logo_width:'280',brand_slogan:'Praktično. Provjereno. Pouzdano.',csrf:csrf(branding.text)}))brandForm.set(k,v);
 const logoBytes=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a41cAAAAASUVORK5CYII=','base64');
 for(const key of ['brand_logo','brand_logo_dark','brand_icon'])brandForm.set(key,new Blob([logoBytes],{type:'image/png'}),key+'.png');
 let brandRes=await fetch(base+'admin.php?page=branding',{method:'POST',body:brandForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(brandRes.status,303);await brandRes.text();
 const brandScreen=await request('admin.php?page=branding',admin);const brandPaths=[...brandScreen.text.matchAll(new RegExp('class="branding-preview[^"]*"><img src="(uploads/[a-f0-9]+[.]png)"','g'))].map(x=>x[1]);assert.equal(brandPaths.length,3);
 const brandHome=await request('',buyer);assert(brandHome.text.includes('src="'+brandPaths[1]+'"'),'dark homepage uses white logo');assert(brandHome.text.includes('src="'+brandPaths[0]+'"'),'footer uses main logo');assert(brandHome.text.includes('rel="icon" href="'+brandPaths[2]+'"'));
 assert((await request('?page=cart',buyer)).text.includes('src="'+brandPaths[0]+'"'),'cart uses normal logo');
 await request('admin.php?page=themes',admin,{action:'store_theme',theme:'minimal',csrf:csrf(themes.text)});assert(!(await request('',buyer)).text.includes('src="'+brandPaths[1]+'"'),'minimal theme avoids white header logo');
 brandForm.set('brand_logo_dark',new Blob(['<svg onload="alert(1)"></svg>'],{type:'image/png'}),'bad.png');brandRes=await fetch(base+'admin.php?page=branding',{method:'POST',body:brandForm,headers:{Cookie:admin.cookie},redirect:'manual'});assert.equal(brandRes.status,200);assert((await brandRes.text()).includes('Koristite JPG'));assert((await request('admin.php?page=branding',admin)).text.includes(brandPaths[0]),'failed upload preserves saved logos');
 assert.equal((await request('admin.php?page=branding',admin,{action:'branding',brand_logo_width:'280',brand_slogan:'Test <b>slogan</b>',brand_show_text:'on',csrf:csrf(branding.text)})).status,303);assert((await request('',buyer)).text.includes('Test &lt;b&gt;slogan&lt;/b&gt;'),'slogan escaped');
 assert.equal((await request('admin.php?page=branding',admin,{action:'branding',brand_logo_width:'280',brand_slogan:'',remove_brand_logo:'on',remove_brand_logo_dark:'on',remove_brand_icon:'on',csrf:csrf(branding.text)})).status,303);assert(!(await request('',buyer)).text.includes('class="store-logo"'),'removal falls back to shop name');
 const audit=await request('admin.php?page=audit',admin);assert(audit.text.includes('Dnevnik aktivnosti')&&!audit.text.includes('<td>order.status</td>'),'human-readable audit screen');assert((await request('admin.php?page=audit&activity=order.status',admin)).text.includes('Promijenjen je status narudžbe'));
 assert.equal((await request('admin.php?page=backups',stockStaff)).status,403);assert.equal((await request('admin.php?page=themes',stockStaff)).status,403);
 assert.equal((await request('admin.php?page=backups',stockStaff,{action:'backup_create',csrf:csrf(staffExitForm.text)})).status,403,'warehouse cannot create backups');
 const backupScreen=await request('admin.php?page=backups',admin);assert(backupScreen.text.includes('Ključ oporavka')&&backupScreen.text.includes('Potvrdi vraćanje')===false);assert((await request('admin.php?page=backups',admin,{action:'backup_restore',backup_id:'bad',confirmation:'VRATI',password:'wrong',csrf:csrf(backupScreen.text)})).text.includes('Administratorska lozinka nije ispravna'));
 // Alpha theme persistence, all customer surfaces and protected idempotent demo import.
 for(const theme of ['alpha-pro','alpha-premium','alpha-hybrid']){
   assert.equal((await request('admin.php?page=themes',admin,{action:'store_theme',theme,csrf:csrf(themes.text)})).status,303);
   const home=await request('',buyer);assert(home.text.includes('class="alpha-hero '),'Alpha hero rendered');
   for(const url of ['','?all=1','?page=cart','?page=checkout','?page=product&id=1'])assert((await request(url,buyer)).text.includes('data-store-theme="'+theme+'"'));
   assert(!(await request('?all=1',buyer)).text.includes('class="alpha-hero '),'catalog has no promotional hero');
   for(const article of ['odabir-polirke','priprema-povrsine','brusni-disk']){const r=await request('?page=advice&article='+article,buyer);assert.equal(r.status,200);assert(r.text.includes('alpha-content-page')&&r.text.includes('alpha-article-intro'));}
   for(const section of ['kontakt','dostava','reklamacije','uslovi','privatnost']){const r=await request('?page=info&section='+section,buyer);assert.equal(r.status,200);assert(r.text.includes('alpha-content-page'));}
   assert.equal((await request('?page=advice&article=invalid',buyer)).status,404);
   assert.equal((await request('?page=info&section=invalid',buyer)).status,404);
   if(theme==='alpha-premium'){assert(home.text.includes('alpha-value-section')&&home.text.includes('alpha-advice-section')&&home.text.includes('alpha-rich-footer'));assert(!home.text.includes('120+'));}

 }
 assert.equal((await request('admin.php?page=themes',stockStaff,{action:'alpha_demo',csrf:csrf(staffExitForm.text)})).status,403);
 assert.equal((await request('admin.php?page=themes',admin,{action:'alpha_demo',csrf:'invalid'})).status,403);
 for(let repeat=0;repeat<2;repeat++)assert.equal((await request('admin.php?page=themes',admin,{action:'alpha_demo',csrf:csrf(themes.text)})).status,303);
 const demoSearch=await request('?q=ALPHA-DEMO-001',buyer);assert(demoSearch.text.includes('MAXXT ekscentrična polirka 15 mm'),'SKU search finds seeded product');assert(demoSearch.text.includes('Testna ponuda.'),'demo is clearly labeled');


 const upgradeBrands=spawnSync(php,['-r',`$c=require 'config/local.php';$d=new PDO('mysql:host='.$c['db_host'].';dbname='.$c['db_name'].';charset=utf8mb4',$c['db_user'],$c['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);foreach(explode(';',file_get_contents('database/migrations/012_brands_slider.sql')) as $s)if(trim($s)!=='')$d->exec($s);`],{cwd:root,encoding:'utf8'});assert.equal(upgradeBrands.status,0,upgradeBrands.stderr);
 const brandPage=await request('admin.php?page=brands',admin);assert.equal(brandPage.status,200);assert(brandPage.text.includes('brand-maxxt.png'));
 const sliderPage=await request('admin.php?page=themes',admin);assert(sliderPage.text.includes('slider_interval'));
 assert.equal((await request('admin.php?page=themes',admin,{action:'slider_settings',slider_count:'2',slider_interval:'2',slider_duration:'200',slider_effect:'slide',slider_autoplay:'on',slider_hover_pause:'on',csrf:csrf(sliderPage.text)})).status,303);
 assert.equal((await request('admin.php?page=products',admin,{action:'hero_publish',id:'1',csrf:csrf(sliderPage.text)})).status,303);
 const sliderHome=await request('',buyer);assert.equal((sliderHome.text.match(/class="alpha-product-slide"/g)||[]).length,2);assert(sliderHome.text.includes('data-interval="2000"')&&sliderHome.text.includes('data-effect="slide"'));
 const firstSlide=sliderHome.text.split('class="alpha-product-slide"')[1].split('</article>')[0];assert(firstSlide.includes('?page=product&amp;id=1')||firstSlide.includes('?page=product&id=1'),'old article republished into first slot');
 const brandCatalog=await request('?brand=1',buyer);assert.equal(brandCatalog.status,200);assert(!brandCatalog.text.includes('class="alpha-hero '));assert(brandCatalog.text.includes('MAXXT'));
 assert.equal((await request('admin.php?page=brands',admin,{action:'brand',id:'1',name:'MAXXT',slug:'maxxt',logo_url:'assets/alpha/brand-maxxt.png',website:'https://maxxttools.com/',logo_dark:'1',sort_order:'10',active:'on',show_on_home:'on',csrf:csrf(brandPage.text)})).status,303);
 assert.equal((await request('admin.php?page=brands',admin,{action:'brand',id:'1',name:'MAXXT',slug:'maxxt',logo_url:'javascript:alert(1)',website:'https://maxxttools.com/',logo_dark:'1',sort_order:'10',csrf:csrf(brandPage.text)})).status,200,'invalid logo URL rejected');
 console.log('PASS: brand management, filtering, publication action and slider options through HTTP');
 const modePage=await request('admin.php?page=settings',admin);
 assert.equal((await request('admin.php?page=settings',stockStaff,{action:'inventory_mode',inventory_enabled:'0',csrf:csrf(staffExitForm.text)})).status,403,'inventory option requires settings permission');
 assert.equal((await request('admin.php?page=settings',admin,{action:'inventory_mode',inventory_enabled:'0',csrf:csrf(modePage.text)})).status,303);
 assert.equal((await request('admin.php?page=inventory',admin)).status,403);
 assert(!(await request('admin.php?page=products',admin)).text.includes('<th>Slobodno</th>'));
 const freeSearch=await request('?q=ALPHA-DEMO-006',buyer);assert(!freeSearch.text.includes('stock-empty')&&!freeSearch.text.includes('Na lageru'),'public stock indicators hidden');
 const zeroId=freeSearch.text.match(/name="id" value="(\d+)"/)[1];
 const zeroDetail=await request('?page=product&id='+zeroId,buyer);assert(!zeroDetail.text.includes('Dostupno na lageru'));assert(!zeroDetail.text.includes('class="buy-button" disabled'));
 await request('?page=add',buyer,{id:zeroId,qty:'3',csrf:csrf(zeroDetail.text)});
 const freeCheckout=await request('?page=checkout',buyer);assert.equal(freeCheckout.status,200);
 const freeToken=freeCheckout.text.match(/name="checkout_token" value="([a-f0-9]+)"/)[1];
 assert.equal((await request('?page=checkout',buyer,{csrf:csrf(freeCheckout.text),checkout_token:freeToken,customer_name:'Bez lagera',email:'untracked@example.test',phone:'123',address:'Test',city:'Test',note:'Test',shipping_method:'1',payment_method:'cod'})).status,303);
 assert((await request('?page=success',buyer)).text.includes('Hvala na narudžbi'));
 assert.equal((await request('admin.php?page=settings',admin,{action:'inventory_mode',inventory_enabled:'1',csrf:csrf(modePage.text)})).status,303);
 assert((await request('?page=product&id='+zeroId,buyer)).text.includes('class="buy-button" disabled'));
 const mailPage=await request('admin.php?page=mail',admin);
 assert.equal((await request('admin.php?page=mail',admin,{action:'mail_settings',mail_contact_enabled:'on',mail_transport:'smtp',mail_host:'127.0.0.1',mail_port:'9',mail_security:'ssl',mail_username:'',mail_from:'shop@example.test',mail_from_name:'Test',mail_recipients:'contacts@example.test',mail_base_url:'https://example.test',csrf:csrf(mailPage.text)})).status,303);
 const contactPage=await request('?page=info&section=kontakt',buyer);assert(contactPage.text.includes('class="alpha-contact-form"'));
 const contactToken=contactPage.text.match(/name="contact_token" value="([a-f0-9]+)"/)[1];
 assert.equal((await request('?page=contact',buyer,{csrf:'invalid'})).status,403);
 const contactPost={csrf:csrf(contactPage.text),contact_token:contactToken,contact_name:'Šefik Čolić',contact_email:'sef@example.test',contact_phone:'061123',contact_message:'Kontakt iz HTTP testa.',contact_consent:'1'};
 assert.equal((await request('?page=contact',buyer,{...contactPost,contact_email:'bad'})).status,303);
 assert((await request('?page=info&section=kontakt',buyer)).text.includes('ispravan e-mail'));
 assert.equal((await request('?page=contact',buyer,contactPost)).status,303);
 assert((await request('?page=info&section=kontakt&sent=1',buyer)).text.includes('Vaš upit je zaprimljen'));
 assert((await request('admin.php?page=mail',admin)).text.includes('Kontakt upit'));
 const contactAdmin=await request('admin.php?page=contact',admin);assert.equal(contactAdmin.status,200);assert(contactAdmin.text.includes('contact_map_embed')&&contactAdmin.text.includes('contact_recipients'));
 assert.equal((await request('admin.php?page=contact',stockStaff)).status,403,'private contact editor requires settings permission');
 const contactSettings={action:'contact_settings',csrf:csrf(contactAdmin.text),contact_company:'Alpha Čedo',contact_title:'Kontaktirajte nas',contact_email:'public@example.test',sender_phone:'+387 61 401 860',contact_address:'Melente Bošnjaka 9, Kakanj',contact_recipients:'private-leads@example.test',contact_map_embed:'https://www.google.com/maps/embed?pb=test',contact_form_enabled:'on',contact_map_enabled:'on',contact_topbar_enabled:'on',mail_contact_enabled:'on'};
 assert.equal((await request('admin.php?page=contact',stockStaff,{...contactSettings,csrf:csrf(staffExitForm.text)})).status,403);
 assert.equal((await request('admin.php?page=contact',admin,{...contactSettings,csrf:'invalid'})).status,403);
 const invalidMap=await request('admin.php?page=contact',admin,{...contactSettings,contact_map_embed:'https://evil.test/maps/embed'});assert.equal(invalidMap.status,200);assert(invalidMap.text.includes('mora biti HTTPS Google Maps'));
 assert.equal((await request('admin.php?page=contact',admin,contactSettings)).status,303);
 const savedContact=await request('?page=info&section=kontakt',buyer);assert(savedContact.text.includes('Alpha Čedo')&&savedContact.text.includes('<iframe')&&savedContact.text.includes('public@example.test'));assert(!savedContact.text.includes('private-leads@example.test'));
 console.log('PASS: contact administration, CSRF and permissions, safe map embed and private recipients through HTTP');
 console.log('PASS: HTTP inventory toggle, zero-stock checkout and contact validation/queue');
 console.log('PASS: three Alpha layouts, persisted selection, SKU search and protected repeatable demo import');
 console.log('PASS: HTTP checkout, paid preparation to dispatched/paid, courier and tracking, order filters, typed exits, notes, reversals, CSV and separate warehouse/finance permissions; storefront regressions pass');
}catch(e){console.error(logs);throw e;}finally{server.kill();if(existsSync(config))unlinkSync(config);}
