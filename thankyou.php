<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Placed</title>
<style>
body{margin:0;font-family:Arial,sans-serif;background:#f7f4ef;color:#241b16;min-height:100vh;display:flex;align-items:center;justify-content:center}.box{background:#fff;border:1px solid #eadfce;border-radius:8px;padding:32px;width:min(460px,92vw);text-align:center;box-shadow:0 12px 30px rgba(0,0,0,.08)}.ok{font-size:42px;color:#16a34a;font-weight:900}h1{color:#9a2418}button{border:0;border-radius:6px;background:#9a2418;color:#fff;padding:13px 18px;font-weight:900;cursor:pointer}
</style>
</head>
<body>
<main class="box"><div class="ok">OK</div><h1>Order Placed</h1><p>Your order <strong id="order"></strong> has been sent to the kitchen.</p><button onclick="location.href='orderstatus.php'">Track Order</button></main>
<script>const id=localStorage.getItem('lastOrderId');document.getElementById('order').innerText=id?'ORD'+id:'is ready';</script>
</body>
</html>
