<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/table_repository.php';
require_once __DIR__ . '/api/db.php';

$tableNumber = filter_var($_GET['table'] ?? ($_SESSION['table_number'] ?? null), FILTER_VALIDATE_INT);
$table = $tableNumber ? set_customer_table($conn, $tableNumber) : current_customer_table($conn);

$itemsResult = $conn->query("
    SELECT mi.*, c.category_name
    FROM menu_items mi
    JOIN categories c ON c.id = mi.category_id
    ORDER BY c.category_name, mi.item_name
");
$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $row['resolved_image'] = menu_image_path($row);
    $items[] = $row;
}
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restaurant Menu</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#fff8ef;color:#23150f}.hero{background:linear-gradient(rgba(42,18,8,.72),rgba(42,18,8,.72)),url('vegitarian/paneer_butter_masala.jpg') center/cover;min-height:230px;color:#fff;padding:22px;display:flex;align-items:flex-end}.hero-inner{width:100%;max-width:1260px;margin:0 auto}.brand{font-size:38px;font-weight:900;margin:0}.sub{margin:8px 0 0;color:#ffe9c7}.table-chip{display:inline-flex;gap:8px;align-items:center;background:#f7b733;color:#2a1208;border-radius:999px;padding:8px 13px;font-weight:900;margin-bottom:18px}.layout{display:grid;grid-template-columns:1fr 360px;gap:20px;max-width:1260px;margin:0 auto;padding:20px}.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;position:sticky;top:0;background:#fff8ef;padding:12px 0;z-index:2}.toolbar input,.toolbar select{border:1px solid #e0cbb4;border-radius:8px;padding:12px;min-width:170px;background:#fff}.menu{display:grid;grid-template-columns:repeat(auto-fill,minmax(225px,1fr));gap:16px}.dish{background:#fff;border:1px solid #ead7c3;border-radius:8px;overflow:hidden;box-shadow:0 10px 24px rgba(86,45,17,.08);display:flex;flex-direction:column}.dish.unavailable{opacity:.55}.photo{height:158px;width:100%;object-fit:cover;background:#ead7c3}.body{padding:14px;display:flex;flex-direction:column;gap:8px;flex:1}.topline{display:flex;justify-content:space-between;gap:8px;align-items:center}.tag{font-size:12px;font-weight:900;padding:5px 8px;border-radius:999px}.veg{background:#e4f7e8;color:#137236}.nonveg{background:#ffe6e4;color:#a32017}.name{font-size:18px;font-weight:900}.desc{font-size:14px;color:#6e5e51;min-height:38px}.add{border:0;border-radius:8px;background:#a32017;color:#fff;padding:12px;font-weight:900;cursor:pointer;margin-top:auto}.add:disabled{background:#999}.cart{position:sticky;top:16px;background:#2a1208;color:#fff;border-radius:8px;padding:16px;height:max-content;box-shadow:0 14px 30px rgba(42,18,8,.25)}.cart h2{margin:0 0 12px}.cart-line{display:grid;grid-template-columns:1fr auto;gap:8px;border-bottom:1px solid rgba(255,255,255,.16);padding:10px 0}.qty{display:flex;gap:6px;align-items:center}.qty button{width:28px;height:28px;border:0;border-radius:6px;background:#f7b733;color:#2a1208;font-weight:900;cursor:pointer}.total{display:flex;justify-content:space-between;font-size:21px;font-weight:900;margin:16px 0}.payment{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}.payment label{border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:9px;cursor:pointer}.checkout,.request{width:100%;border:0;border-radius:8px;padding:13px;font-weight:900;cursor:pointer}.checkout{background:#18a34a;color:#fff}.checkout:disabled{background:#777}.request{background:#fff;color:#2a1208;margin-top:8px}.requests{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}.notice{max-width:760px;margin:24px auto;background:#fff;border:1px solid #ead7c3;border-radius:8px;padding:22px;text-align:center}.msg{margin-top:12px}.ok{background:#e9f8ed;color:#136d35;padding:10px;border-radius:8px}.err{background:#ffe6e4;color:#8f1d14;padding:10px;border-radius:8px}@media(max-width:960px){.layout{grid-template-columns:1fr}.cart{position:static}.brand{font-size:30px}}@media(max-width:560px){.menu{grid-template-columns:1fr}.toolbar input,.toolbar select{width:100%;min-width:0}.payment,.requests{grid-template-columns:1fr}.hero{min-height:190px}}
</style>
</head>
<body>
<?php if (!$table): ?>
<main class="notice">
<h1>Scan Your Table QR</h1>
<p>This menu opens only from a restaurant table QR code so your order reaches the correct table.</p>
<p>Please ask the waiter for your table QR.</p>
</main>
<?php else: ?>
<section class="hero">
<div class="hero-inner">
<div class="table-chip">Table <?php echo (int)$table['table_number']; ?> · <?php echo e($table['status']); ?></div>
<h1 class="brand">Fresh From The Kitchen</h1>
<p class="sub">Browse the menu, place your order, and call the waiter without leaving your table.</p>
</div>
</section>
<main class="layout">
<section>
<div class="toolbar">
<input id="search" placeholder="Search dishes">
<select id="category"><option value="">All Categories</option><?php foreach ($categories as $cat): ?><option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['category_name']); ?></option><?php endforeach; ?></select>
<select id="foodType"><option value="">Veg and Non-Veg</option><option value="1">Veg Only</option><option value="2">Non-Veg Only</option></select>
</div>
<div id="menu" class="menu"></div>
</section>
<aside class="cart">
<h2>Your Order</h2>
<div id="cartLines"></div>
<div class="total"><span>Total</span><span>Rs. <span id="total">0.00</span></span></div>
<div class="payment"><label><input type="radio" name="payment" value="Counter" checked> Counter</label><label><input type="radio" name="payment" value="Cash"> Cash</label><label><input type="radio" name="payment" value="UPI"> UPI</label><label><input type="radio" name="payment" value="Card"> Card</label></div>
<button id="checkout" class="checkout" onclick="placeOrder()">Place Order</button>
<div class="requests">
<button class="request" onclick="requestWaiter('Need Water')">Water</button>
<button class="request" onclick="requestWaiter('Need Spoon')">Spoon</button>
<button class="request" onclick="requestWaiter('Need Tissue')">Tissue</button>
<button class="request" onclick="requestWaiter('Need Assistance')">Assistance</button>
<button class="request" onclick="requestWaiter('Need Bill')">Need Bill</button>
</div>
<div id="message" class="msg"></div>
</aside>
</main>
<script>
const items=<?php echo json_encode($items); ?>;
const tableId=<?php echo (int)$table['id']; ?>;
const tableNumber=<?php echo (int)$table['table_number']; ?>;
let cart=JSON.parse(localStorage.getItem('cart_table_'+tableNumber)||'{}');
const params=new URLSearchParams(location.search);
if(params.get('type')==='veg')document.addEventListener('DOMContentLoaded',()=>document.getElementById('foodType').value='1');
if(params.get('type')==='nonveg')document.addEventListener('DOMContentLoaded',()=>document.getElementById('foodType').value='2');
function money(n){return Number(n).toFixed(2);}
function save(){localStorage.setItem('cart_table_'+tableNumber,JSON.stringify(cart));}
function filtered(){const q=document.getElementById('search').value.toLowerCase();const cat=document.getElementById('category').value;const type=document.getElementById('foodType').value;return items.filter(i=>(!q||String(i.item_name+' '+(i.description||'')).toLowerCase().includes(q))&&(!cat||String(i.category_id)===cat)&&(!type||String(i.category_id)===type));}
function renderMenu(){const wrap=document.getElementById('menu');wrap.innerHTML='';filtered().forEach(i=>{const el=document.createElement('article');el.className='dish '+(i.availability!=='available'?'unavailable':'');el.innerHTML='<img class="photo" alt=""><div class="body"><div class="topline"><span class="tag"></span><strong></strong></div><div class="name"></div><div class="desc"></div><button class="add"></button></div>';el.querySelector('img').src=i.resolved_image||'';el.querySelector('.tag').className='tag '+(Number(i.category_id)===2?'nonveg':'veg');el.querySelector('.tag').innerText=Number(i.category_id)===2?'Non-Veg':'Veg';el.querySelector('strong').innerText='Rs. '+money(i.price);el.querySelector('.name').innerText=i.item_name;el.querySelector('.desc').innerText=i.description||'';const b=el.querySelector('button');b.innerText=i.availability==='available'?'Add':'Unavailable';b.disabled=i.availability!=='available';b.onclick=()=>add(i);wrap.appendChild(el);});if(!wrap.children.length)wrap.innerHTML='<p>No dishes match your filters.</p>';}
function add(i){const id=i.id;cart[id]=cart[id]||{id:Number(i.id),name:i.item_name,price:Number(i.price),quantity:0};cart[id].quantity++;save();renderCart();}
function changeQty(id,delta){if(!cart[id])return;cart[id].quantity+=delta;if(cart[id].quantity<=0)delete cart[id];save();renderCart();}
function renderCart(){const lines=document.getElementById('cartLines');lines.innerHTML='';let total=0;Object.values(cart).forEach(i=>{total+=i.price*i.quantity;const row=document.createElement('div');row.className='cart-line';row.innerHTML='<div><strong></strong><div></div></div><div class="qty"><button>-</button><span></span><button>+</button></div>';row.querySelector('strong').innerText=i.name;row.querySelector('div div').innerText='Rs. '+money(i.price*i.quantity);row.querySelector('span').innerText=i.quantity;row.querySelectorAll('button')[0].onclick=()=>changeQty(i.id,-1);row.querySelectorAll('button')[1].onclick=()=>changeQty(i.id,1);lines.appendChild(row);});document.getElementById('total').innerText=money(total);document.getElementById('checkout').disabled=Object.keys(cart).length===0;}
function message(text,ok){const el=document.getElementById('message');el.className='msg '+(ok?'ok':'err');el.innerText=text;}
function placeOrder(){const payment=document.querySelector('input[name="payment"]:checked').value;const payload={table_id:tableId,table_number:tableNumber,payment_method:payment,items:Object.values(cart).map(i=>({id:i.id,quantity:i.quantity}))};fetch('api/place_order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}).then(r=>r.json()).then(data=>{if(!data.success){message(data.message||'Order failed.',false);return;}localStorage.setItem('lastOrderId',data.order_id);localStorage.removeItem('cart_table_'+tableNumber);cart={};renderCart();message('Order '+data.display_order_id+' sent to the kitchen.',true);setTimeout(()=>location.href='orderstatus.php',900);}).catch(()=>message('Connection error. Please try again.',false));}
function requestWaiter(type){fetch('api/waiter_request.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({request_type:type})}).then(r=>r.json()).then(data=>message(data.message||'Request sent.',!!data.success)).catch(()=>message('Could not send request.',false));}
['search','category','foodType'].forEach(id=>document.getElementById(id).addEventListener('input',renderMenu));
renderMenu();renderCart();
</script>
<?php endif; ?>
</body>
</html>
