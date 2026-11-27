<?php
session_start();
require __DIR__.'/lib/api.php';
$error='';
try {
  $products=api_request('products.php');
  $categories=api_request('categories.php');
  $groups=api_request('groups.php');
  $matrix=api_request('product-matrices.php');
  $user=null;$prefs=[];$events=[];
  if(isset($_SESSION['user_id'])){
    $user=api_request('users.php','GET',['user_id'=>$_SESSION['user_id']]);
    $prefs=api_request('preferences.php','GET',['user_id'=>$_SESSION['user_id']]);
    $events=api_request('events.php','GET',['user_id'=>$_SESSION['user_id']]);
  }
  $gp=[];foreach($groups['group_preferences'] as $r)$gp[$r['group_id']][$r['preference_id']]=$r['weight'];
  $gc=[];foreach($groups['group_categories'] as $r)$gc[$r['group_id']][$r['category_id']]=$r['weight'];
  $up=[];foreach($prefs as $r)$up[$r['id']]=$r['weight'];
  $pp=[];foreach($matrix['product_preferences'] as $r)$pp[$r['product_id']][$r['preference_id']]=$r['weight'];
  $groupId=$user['group_id']??1;
  foreach($products as &$p){
    $score=0;
    for($i=1;$i<=12;$i++){
      $score+=($up[$i]??0)*($pp[$p['id']][$i]??0);
      $score+=0.22*($gp[$groupId][$i]??0)*($pp[$p['id']][$i]??0);
    }
    $score+=0.18*($gc[$groupId][$p['category_id']]??0);
    foreach($events as $ev)if((int)$ev['product_id']===(int)$p['id'])$score+=($ev['event_type']==='purchase'?0.08:0.03);
    $p['recommendation_score']=round($score,4);
  } unset($p);
  usort($products,fn($a,$b)=>($b['recommendation_score']<=>$a['recommendation_score']) ?: ($a['id']<=>$b['id']));
} catch(Throwable $e){$error=$e->getMessage();$products=[];$categories=[];$user=null;}
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CinemaGo — магазин</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><a class="logo" href="index.php">CINEMA<span>GO</span></a><nav><a href="cart.php">Корзина</a><a href="#catalog">Каталог</a><a href="#about">О магазине</a></nav><div class="account"><?php if($user):?>Привет, <?=h($user['display_name'])?> · <a href="logout.php">Выйти</a><?php else:?><a href="#login">Войти</a><?php endif;?></div></header>
<main>
<section class="hero"><div><p class="eyebrow">КИНО НАЧИНАЕТСЯ ЗДЕСЬ</p><h1>Твоя коллекция.<br><span>Твои впечатления.</span></h1><p>Диски, билеты и вещи для настоящих поклонников кино.</p><a class="button" href="#catalog">Перейти в каталог</a></div><div class="hero-art"><div class="disc"></div><div class="ticket">CINEMA<br>GO<br><small>ADMIT ONE</small></div></div></section>
<?php if($error):?><div class="notice"><?=h($error)?>. Убедитесь, что API запущен в отдельном терминале.</div><?php endif;?>
<?php if(!$user):?><section class="login" id="login"><div><h2>Войти в аккаунт</h2><p>Войдите, чтобы получить персональные рекомендации.</p></div><form method="post" action="login.php"><input name="login" placeholder="Логин (например, user1)" required><input name="password" type="password" placeholder="Пароль" required><button class="button">Войти</button></form></section><?php else:?><div class="welcome">Персональная выдача для группы «<?=h($user['group_name'])?>». Товары отсортированы по баллу рекомендации.</div><?php endif;?>
<section id="catalog"><div class="section-title"><div><p class="eyebrow">ВЫБИРАЙ СВОЁ</p><h2>Каталог магазина</h2></div><span><?=count($products)?> товаров</span></div>
<div class="filters"><input id="search" placeholder="Поиск по каталогу..."><select id="category"><option value="">Все категории</option><?php foreach($categories as $c):?><option value="<?=h($c['category_name'])?>"><?=h($c['category_name'])?></option><?php endforeach;?></select></div>
<div class="grid" id="grid"><?php foreach($products as $p):?><article class="card" data-name="<?=h(mb_strtolower($p['name']))?>" data-category="<?=h($p['category_name'])?>"><div class="cover"><img src="<?=h($p['image'])?>" alt=""><span class="badge"><?=h($p['product_type'])?></span></div><div class="card-body"><div class="sub"><?=h($p['category_name'])?> · <?=h($p['subcategory_name'])?></div><h3><?=h($p['name'])?></h3><p><?=h($p['description'])?></p><div class="meta"><strong><?=number_format((float)$p['price'],0,'.',' ')?> ₸</strong><span>Остаток: <?=h($p['stock'])?></span></div><div class="score">Рекомендация: <?=h($p['recommendation_score'])?></div><form method="post" action="action.php"><input type="hidden" name="product_id" value="<?=h($p['id'])?>"><input type="hidden" name="event_type" value="<?=h($p['product_type']==='Билет'?'ticket':'cart')?>"><button class="buy"><?= $p['product_type']==='Билет'?'Выбрать билет':'Добавить в корзину'?></button></form></div></article><?php endforeach;?></div>
</section>
<section id="about" class="about"><h2>Больше, чем магазин</h2><p>CinemaGo объединяет фильмы на Blu-ray и DVD, билеты в кино, саундтреки, постеры, аксессуары и коллекционные издания. Каталог содержит 200 учебных позиций, а персональная система подбирает товары на основе предпочтений и действий пользователя.</p></section>
<footer>© 2026 CinemaGo · Учебный демонстрационный проект</footer></main>
<script>
const search=document.getElementById('search'),category=document.getElementById('category');
function filter(){const q=search.value.toLowerCase(),c=category.value;document.querySelectorAll('.card').forEach(el=>{el.style.display=(el.dataset.name.includes(q)&&(!c||el.dataset.category===c))?'':'none';});}
search.addEventListener('input',filter);category.addEventListener('change',filter);
</script></body></html>
