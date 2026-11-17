const files={
maxxt:['png','https://maxxttools.com/cdn/shop/files/LOGO.png?v=1757665551&width=600'],
mipa:['jpg','https://www.mipa-paints.com/fileadmin/mipa/downloads/logos/mipa_pcs_72dpi.jpg'],
chemicalworkz:['svg','https://cdn.prod.website-files.com/67cac507eec7d203410f1039/67daa7c78a4542d58837f2f3_logo_chemicalworkz.svg'],
chemicalalt:['svg','https://cdn.prod.website-files.com/67bc799e8e387cbe01b4ea24/67dbd874531bd5f8c7a17e5f_logo_chemicalworkz.svg'],
ps:['png','https://psdetailproducts.com/cdn/shop/files/P_S_Red_Logo.png?v=1717528232&width=600'],
ewocar:['webp','https://ewocar.com/wp-content/uploads/2025/10/ewocar-logo-new.webp'],
'3d':['png','https://3dproducts.com/cdn/shop/files/Asset_6_3x_b38999b4-824d-449d-90c6-5269015c71e2.png?v=1715739197&width=600']
}; await Promise.all(Object.entries(files).map(async([name,[ext,url]])=>{try{const r=await fetch(url,{signal:AbortSignal.timeout(20000)});if(!r.ok)throw Error(r.status);const b=Buffer.from(await r.arrayBuffer());if(b.length>1000000)throw Error('Too large');console.log('BRAND_FILE '+name+' '+ext+' '+b.toString('base64'));}catch(e){console.log('BRAND_ERROR '+name+' '+e.message)}}));