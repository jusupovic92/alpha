const sites={maxxt:'https://maxxttools.com/',mipa:'https://www.mipa-paints.com/service/downloads/logos/',chemicalworkz:'https://www.chemicalworkz.de/',ps:'https://psdetailproducts.com/',ewocar:'https://ewocar.com/','3d':'https://3dproducts.com/'};
await Promise.all(Object.entries(sites).map(async([name,url])=>{try{
 const response=await fetch(url,{signal:AbortSignal.timeout(15000),headers:{'User-Agent':'Mozilla/5.0'}});if(!response.ok)throw Error('HTTP '+response.status);const html=await response.text();const found=[];
 for(const match of html.matchAll(/<img\b[^>]*>/gi)){const tag=match[0];if(!/logo/i.test(tag))continue;for(const src of tag.matchAll(/(?:src|data-src)=["']([^"']+)["']/gi)){if(src[1].startsWith('data:'))continue;found.push(new URL(src[1].replaceAll('&amp;','&'),url).href);}}
 for(const match of html.matchAll(/["']([^"']*logo[^"']*\.(?:svg|png|jpg|webp)(?:\?[^"']*)?)["']/gi)){if(match[1].length<500&&!match[1].includes(' '))try{found.push(new URL(match[1].replaceAll('&amp;','&'),url).href)}catch{}}
 console.log('BRAND_ASSETS '+name+' '+JSON.stringify([...new Set(found)].slice(0,16)));
 }catch(e){console.log('BRAND_ASSETS_ERROR '+name+' '+e.message)}}));
