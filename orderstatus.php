<?php require_once __DIR__ . '/includes/app.php'; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Tracking</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#fff8ef;color:#24140d}.wrap{max-width:860px;margin:24px auto;padding:18px}.panel{background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:22px;box-shadow:0 10px 24px rgba(86,45,17,.08)}.brand{font-size:30px;font-weight:900;color:#a32017}.meta{color:#66564b}.steps{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin:22px 0}.step{height:10px;border-radius:999px;background:#ead7c3}.step.active{background:#18a34a}.badge{display:inline-block;border-radius:999px;padding:8px 12px;background:#2563eb;color:#fff;font-weight:900}.accepted{background:#7c3aed}.preparing{background:#f59e0b}.ready{background:#16a34a}.served{background:#0f766e}.completed{background:#64748b}.items{margin-top:18px}.item{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:10px 0}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.btn{border:0;border-radius:8px;background:#a32017;color:#fff;padding:12px 14px;font-weight:900;text-decoration:none;cursor:pointer}.btn.gold{background:#f7b733;color:#2a1208}@media(max-width:620px){.steps{grid-template-columns:repeat(3,1fr)}}
</style>
</head>
<body>
<main class="wrap">
<section class="panel">
<div class="brand">Order Tracking</div>
<p class="meta"><strong id="orderId">Order</strong> · Table <span id="table">-</span></p>
<p>Status: <span id="status" class="badge">Loading</span></p>
<div id="steps" class="steps"></div>
<div id="items" class="items"></div>
<p class="meta" id="updated">Loading...</p>
<div class="actions">
<a class="btn gold" href="menu.php">Back To Menu</a>
<a class="btn" href="payment.php">View Bill</a>
</div>
</section>
</main>
<script>
const orderId=localStorage.getItem('lastOrderId');
const statuses=['New','Accepted','Preparing','Ready','Served','Completed'];
function cls(status){return String(status||'').toLowerCase().replace(/[^a-z]/g,'');}
function renderSteps(status){const wrap=document.getElementById('steps');wrap.innerHTML='';const idx=statuses.indexOf(status);statuses.forEach((s,i)=>{const d=document.createElement('div');d.className='step '+(i<=idx?'active':'');d.title=s;wrap.appendChild(d);});}
function load(){const url=orderId?'api/get_order_status.php?order_id='+encodeURIComponent(orderId):'api/get_order_status.php';fetch(url).then(r=>r.json()).then(data=>{if(!data.success){document.getElementById('status').innerText='No Order';renderSteps('');return;}document.getElementById('orderId').innerText=data.display_order_id;document.getElementById('table').innerText=data.table_number;const badge=document.getElementById('status');badge.innerText=data.status;badge.className='badge '+cls(data.status);renderSteps(data.status);const items=document.getElementById('items');items.innerHTML='<h3>Items</h3>';data.items.forEach(i=>{const row=document.createElement('div');row.className='item';const left=document.createElement('span');const right=document.createElement('strong');left.innerText=i.item_name+' x '+i.quantity;right.innerText='Rs. '+Number(i.price*i.quantity).toFixed(2);row.appendChild(left);row.appendChild(right);items.appendChild(row);});document.getElementById('updated').innerText='Last updated '+new Date().toLocaleTimeString();}).catch(()=>document.getElementById('updated').innerText='Unable to refresh status.');}
load();setInterval(load,5000);
</script>
</body>
</html>
