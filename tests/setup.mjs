import assert from 'node:assert/strict';
import {spawn,spawnSync} from 'node:child_process';
import {existsSync,writeFileSync,unlinkSync,mkdtempSync,rmSync} from 'node:fs';
import {randomBytes} from 'node:crypto';
import {tmpdir} from 'node:os';
import net from 'node:net';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const php=process.env.PHP_BINARY||'php';
const config=path.join(root,'config/local.php'),keyPath=path.join(root,'config/setup.key');
assert(!existsSync(config)&&!existsSync(keyPath),'Refusing existing configuration/key');
const name=(process.env.TEST_DB_NAME||'webshop_test')+'_setup';assert(/^webshop_test(?:_[a-z0-9]+)+$/.test(name));
const env={...process.env,SETUP_TEST_DB:name};
const fixture=spawnSync(php,['-r',`$n=getenv('SETUP_TEST_DB');if(!preg_match('/^webshop_test(?:_[a-z0-9]+)+$/D',$n))exit(2);$d=new PDO('mysql:host='.(getenv('TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('TEST_DB_PORT')?:'33317'),getenv('TEST_DB_USER')?:'root',getenv('TEST_DB_PASSWORD')?:'local_test_only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$d->exec('CREATE DATABASE IF NOT EXISTS '.$n.' CHARACTER SET utf8mb4');$d->exec('USE '.$n);if($d->query('SHOW TABLES')->fetchColumn())exit(3);foreach(explode(';',file_get_contents('database/install.sql')) as $s)if(trim($s)!=='')$d->exec($s);`],{cwd:root,env,encoding:'utf8'});
assert.equal(fixture.status,0,fixture.stderr+' Test setup database must be empty.');
const quote=s=>"'"+s.replaceAll('\\','\\\\').replaceAll("'","\\'")+"'";
const cfg={db_host:(process.env.TEST_DB_HOST||'127.0.0.1')+';port='+(process.env.TEST_DB_PORT||'33317'),db_name:name,db_user:process.env.TEST_DB_USER||'root',db_pass:process.env.TEST_DB_PASSWORD||'local_test_only'};
writeFileSync(config,'<?php return ['+Object.entries(cfg).map(([k,v])=>quote(k)+'=>'+quote(v)).join(',')+'];');
const temp=mkdtempSync(path.join(tmpdir(),'webshop-setup-test-'));
const prepend=path.join(temp,'https.php');writeFileSync(prepend,"<?php $_SERVER['HTTPS']='on';");
const probe=net.createServer();await new Promise(r=>probe.listen(0,'127.0.0.1',r));const port=probe.address().port;await new Promise(r=>probe.close(r));
let server,cookie='';const key=randomBytes(32).toString('hex');
async function start(https){server=spawn(php,[...(https?['-d','auto_prepend_file="'+prepend.replaceAll('\\','/')+'"']:[]),'-S','127.0.0.1:'+port,'-t',path.join(root,'public')],{stdio:'ignore'});for(let i=0;i<40;i++){assert.equal(server.exitCode,null);try{await fetch('http://127.0.0.1:'+port+'/setup-admin.php');return;}catch{await new Promise(r=>setTimeout(r,100));}}throw Error('Server failed to start');}
async function stop(){if(server&&server.exitCode===null){const closed=new Promise(r=>server.once('exit',r));server.kill();await closed;}}
async function request(data){const r=await fetch('http://127.0.0.1:'+port+'/setup-admin.php',{headers:{...(cookie?{Cookie:cookie}:{}),...(data?{'Content-Type':'application/x-www-form-urlencoded'}:{})},...(data?{method:'POST',body:new URLSearchParams(data)}:{})});if(r.headers.get('set-cookie'))cookie=r.headers.get('set-cookie').split(';')[0];return {status:r.status,text:await r.text()};}
try{
 await start(false);assert.equal((await request()).status,404,'disabled without private key');
 writeFileSync(keyPath,key);assert.equal((await request()).status,403,'HTTPS mandatory');await stop();cookie='';await start(true);
 let r=await request();assert.equal(r.status,200);const csrf=r.text.match(/name="csrf" value="([a-f0-9]+)"/)[1];
 const fields={csrf,setup_key:key,name:'Setup Admin',username:'setup-admin',email:'setup@example.test',password:'setup-test-password-123',password_confirm:'setup-test-password-123'};
 assert.equal((await request({...fields,csrf:'bad'})).status,403,'CSRF blocked');
 assert.equal((await request({...fields,setup_key:'wrong'})).status,403,'wrong key blocked');
 assert.equal((await request({...fields,password:'short',password_confirm:'short'})).status,422,'weak password rejected');
 assert.equal((await request({...fields,password_confirm:'different'})).status,422,'confirmation required');
 r=await request(fields);assert.equal(r.status,200);assert(r.text.includes('Administrator je kreiran'));assert(!existsSync(keyPath),'private key removed');
 writeFileSync(keyPath,key);assert.equal((await request()).status,403,'permanent lockout after success');assert.equal((await request(fields)).status,403,'second account rejected');
 const verify=spawnSync(php,['-r',`$c=require 'config/local.php';$d=new PDO('mysql:host='.$c['db_host'].';dbname='.$c['db_name'],$c['db_user'],$c['db_pass']);$u=$d->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);if(count($u)!==1||$u[0]['username']!=='setup-admin'||!password_verify('setup-test-password-123',$u[0]['password_hash'])||!$d->query("SELECT setting_value FROM settings WHERE setting_key='admin_setup_completed'")->fetchColumn())exit(1);`],{cwd:root,encoding:'utf8'});assert.equal(verify.status,0,verify.stderr);
 console.log('PASS: setup disabled by default, HTTPS, CSRF, secret key, password validation, hashed first administrator, key removal and permanent lockout');
}finally{await stop();if(existsSync(config))unlinkSync(config);if(existsSync(keyPath))unlinkSync(keyPath);rmSync(temp,{recursive:true,force:true});}
