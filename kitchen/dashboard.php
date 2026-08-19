<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_staff_area('kitchen');
$token = csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kitchen Dashboard</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#07090c;color:#f8fafc}.top{position:sticky;top:0;z-index:3;background:#111820;border-bottom:1px solid #293544;padding:16px 22px;display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap}.brand{font-size:28px;font-weight:900}.muted{color:#9aa7b5}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:11px 13px;font-weight:900;cursor:pointer}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding:18px 22px}.stat{background:#121b26;border:1px solid #263241;border-radius:8px;padding:16px}.stat b{font-size:32px;color:#f7b733;display:block}.section-title{padding:8px 22px 0;margin:0;font-size:20px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px;padding:16px 22px}.card{background:#121b26;border:1px solid #2c3a4b;border-radius:8px;padding:16px;box-shadow:0 12px 26px rgba(0,0,0,.24)}.card.late{border-color:#dc2626}.head{display:flex;justify-content:space-between;gap:10px}.table{font-size:26px;font-weight:900}.badge{border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;color:#fff}.new{background:#2563eb}.accepted{background:#7c3aed}.preparing{background:#f59e0b}.ready{background:#16a34a}.timer{color:#f7b733;font-weight:900;margin-top:6px}.items{margin-top:12px}.item{border-bottom:1px solid #223044;padding:8px 0}.actions{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px}.actions button{border:0;border-radius:8px;padding:11px;color:#fff;font-weight:900;cursor:pointer}.actions button:disabled{opacity:.35;cursor:not-allowed}.accept{background:#2563eb}.prepare{background:#f59e0b}.readybtn{background:#16a34a}.empty{color:#94a3b8;padding:14px 22px}.vegline{color:#86efac}.nonvegline{color:#fca5a5}@media(max-width:780px){.stats{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr;padding:12px}.top{padding:14px}.actions{grid-template-columns:1fr}}@media(max-width:480px){.stats{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="top">
<div><div class="brand">Kitchen Dashboard</div><div class="muted" id="statusLine">Loading live orders...</div></div>
<button class="btn" onclick="document.documentElement.requestFullscreen()">Full Screen</button>
</header>
<section class="stats">
<div class="stat"><span>Active</span><b id="activeCount">0</b></div>
<div class="stat"><span>New</span><b id="newCount">0</b></div>
<div class="stat"><span>Preparing</span><b id="prepCount">0</b></div>
<div class="stat"><span>Ready</span><b id="readyCount">0</b></div>
</section>
<h2 class="section-title">Veg Orders</h2><section id="vegGrid" class="grid"></section>
<h2 class="section-title">Non-Veg Orders</h2><section id="nonvegGrid" class="grid"></section>
<script>
const csrfToken=<?php echo json_encode($token); ?>;
let latestIds=new Set();
function cls(status){return String(status||'').toLowerCase().replace(/[^a-z]/g,'');}
function nextAllowed(status,next){return {New:['Accepted'],Accepted:['Preparing'],Preparing:['Ready'],Ready:[]}[status]?.includes(next);}
function elapsed(order){const start=new Date((order.start_time||order.order_time).replace(' ','T'));const mins=Math.max(0,Math.floor((Date.now()-start.getTime())/60000));return mins+' min';}
function card(order){const c=document.createElement('article');c.className='card';if(elapsed(order).startsWith('2')||elapsed(order).startsWith('3'))c.classList.add('late');const h=document.createElement('div');h.className='head';h.innerHTML='<div class="table"></div><span class="badge"></span>';h.querySelector('.table').innerText='Table '+order.table_number;h.querySelector('.badge').className='badge '+cls(order.status);h.querySelector('.badge').innerText=order.status;c.appendChild(h);const info=document.createElement('div');info.className='muted';info.innerText='ORD'+order.id+' · Rs. '+Number(order.total_amount).toFixed(2);c.appendChild(info);const t=document.createElement('div');t.className='timer';t.innerText='Timer '+elapsed(order);c.appendChild(t);const mix=document.createElement('div');mix.innerHTML='<span class="vegline">Veg '+order.veg_qty+'</span> · <span class="nonvegline">Non-Veg '+order.nonveg_qty+'</span>';c.appendChild(mix);const items=document.createElement('div');items.className='items';order.items.forEach(i=>{const d=document.createElement('div');d.className='item';d.innerText=i;items.appendChild(d);});c.appendChild(items);const a=document.createElement('div');a.className='actions';a.appendChild(button('Accept','accept',!nextAllowed(order.status,'Accepted'),()=>update(order.id,'Accepted')));a.appendChild(button('Preparing','prepare',!nextAllowed(order.status,'Preparing'),()=>update(order.id,'Preparing')));a.appendChild(button('Ready','readybtn',!nextAllowed(order.status,'Ready'),()=>update(order.id,'Ready')));c.appendChild(a);return c;}
function button(label,klass,disabled,fn){const b=document.createElement('button');b.className=klass;b.disabled=disabled;b.innerText=label;b.onclick=fn;return b;}
function render(orders){const veg=document.getElementById('vegGrid'), non=document.getElementById('nonvegGrid');veg.innerHTML='';non.innerHTML='';orders.forEach(o=>{if(o.veg_qty>0)veg.appendChild(card(o));if(o.nonveg_qty>0)non.appendChild(card(o));});if(!veg.children.length)veg.innerHTML='<div class="empty">No veg orders.</div>';if(!non.children.length)non.innerHTML='<div class="empty">No non-veg orders.</div>';document.getElementById('activeCount').innerText=orders.length;document.getElementById('newCount').innerText=orders.filter(o=>o.status==='New').length;document.getElementById('prepCount').innerText=orders.filter(o=>o.status==='Preparing').length;document.getElementById('readyCount').innerText=orders.filter(o=>o.status==='Ready').length;document.getElementById('statusLine').innerText='Last refreshed '+new Date().toLocaleTimeString();const ids=new Set(orders.map(o=>o.id));orders.forEach(o=>{if(!latestIds.has(o.id)&&latestIds.size>0)new Audio('data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA=').play().catch(()=>{});});latestIds=ids;}
function load(){fetch('get_orders.php').then(r=>r.json()).then(render).catch(()=>document.getElementById('statusLine').innerText='Unable to load orders');}
function update(id,status){const fd=new FormData();fd.append('order_id',id);fd.append('status',status);fd.append('csrf_token',csrfToken);fetch('update_status.php',{method:'POST',body:fd}).then(r=>r.text()).then(t=>{if(t.trim()==='success')load();else alert('Status update failed.');}).catch(()=>alert('Connection error.'));}
load();setInterval(load,5000);
</script>
</body>
</html>
